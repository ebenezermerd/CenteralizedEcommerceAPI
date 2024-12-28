<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FALaravel\Support\Authenticator;
use Spatie\Activitylog\Facades\LogActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\MFATokenService;
use App\Services\BackupCodeService;

class MFAController extends Controller
{
    protected $maxAttempts = 5;
    protected $decayMinutes = 10;
    protected $mfaTokenService;
    protected $backupCodeService;

    public function __construct(
        MFATokenService $mfaTokenService,
        BackupCodeService $backupCodeService
    ) {
        $this->mfaTokenService = $mfaTokenService;
        $this->backupCodeService = $backupCodeService;
    }

    public function setup(Request $request)
    {
        try {
            $user = Auth::user();
            $google2fa = app('pragmarx.google2fa');

            // Generate new secret key
            $secretKey = $google2fa->generateSecretKey();
            
            // Save secret key and enable MFA
            $user->mfa_secret = $secretKey;
            $user->is_mfa_enabled = true;
            $user->save();

            // Generate QR code
            $QRImage = $google2fa->getQRCodeInline(
                config('app.name'),
                $user->email,
                $secretKey
            );

            // Extract the actual base64 image data
            $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $QRImage);

            Activity::create([
                'log_name' => 'mfa',
                'description' => 'MFA setup completed',
                'causer_id' => $user->id,
                'causer_type' => get_class($user),
                'properties' => ['setup_completed' => true]
            ]);

            Log::info('MFA setup completed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            // Generate backup codes
            $backupCodes = $this->generateBackupCodes($user);

            return response()->json([
                'secret' => $secretKey,
                'qrImage' => [
                    'dataUrl' => $QRImage,  // Full data URL for <img> src
                    'base64' => $base64Image, // Raw base64 if needed
                    'downloadUrl' => '/api/auth/mfa/download-qr?key=' . $secretKey
                ],
                'backupCodes' => $backupCodes,
                'message' => 'MFA setup initialized successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('MFA setup failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);
            return response()->json(['error' => 'MFA setup failed'], 500);
        }
    }

    public function verify(Request $request)
    {
        $request->validate([
            'oneTimePassword' => 'required|string|size:6',
            'tempToken' => 'required|string',  // Require the temporary token
            // 'rememberDevice' => 'boolean'
        ]);

        try {
            // Verify the temporary token
            $user = JWTAuth::setToken($request->tempToken)->authenticate();
            if (!$user) {
                return response()->json(['error' => 'Invalid token'], 401);
            }

            // Rate limiting
            $key = 'mfa_attempts_' . $user->id;
            $attempts = Cache::get($key, 0);
            
            if ($attempts >= $this->maxAttempts) {
                return response()->json([
                    'error' => 'Too many attempts',
                    'message' => "Please try again after {$this->decayMinutes} minutes"
                ], 429);
            }

            if ($this->verifyCode($user, $request->oneTimePassword) || 
                $this->verifyBackupCode($user, $request->oneTimePassword)) {
                
                Cache::forget($key);
                
                // // Handle remember device
                // $deviceToken = null;
                // if ($request->rememberDevice) {
                //     $deviceToken = $this->rememberDevice($user, $request);
                // }

                // Generate new tokens after successful MFA
                $newToken = JWTAuth::fromUser($user);
                $refreshToken = JWTAuth::fromUser($user);

                // Invalidate the temporary token
                JWTAuth::setToken($request->tempToken)->invalidate();

                return response()->json([
                    'message' => 'MFA verification successful',
                    // 'deviceToken' => $deviceToken,
                    'accessToken' => $newToken,
                    'refreshToken' => $refreshToken,
                    'role' => $user->getRoleNames()->first(),
                    'expires_in' => JWTAuth::factory()->getTTL() * 60
                ], 200);
            }

            // Increment attempts
            Cache::put($key, $attempts + 1, now()->addMinutes($this->decayMinutes));

            return response()->json([
                'error' => 'Invalid code',
                'attemptsRemaining' => $this->maxAttempts - ($attempts + 1)
            ], 401);
        } catch (\Exception $e) {
            Activity::create([
                'log_name' => 'mfa',
                'description' => 'MFA verification error',
                'properties' => [
                    'error' => $e->getMessage(),
                    'ip' => $request->ip()
                ]
            ]);

            Log::error('MFA verification error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'errorMessage' => 'MFA verification failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function generateBackupCodes(User $user)
    {
        $backupCodes = [];
        for ($i = 0; $i < 10; $i++) {
            $code = Str::random(64);
            $salt = Str::random(16); // Generate a salt value
            $backupCodes[] = [
                'user_id' => $user->id,
                'code' => hash('sha256', $code . $salt),
                'salt' => $salt, // Include the salt value
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('mfa_backup_codes')->insert($backupCodes);
        return $backupCodes;
    }

    protected function verifyBackupCode($user, $code)
    {
        $hashedCode = hash('sha256', $code);
        $backupCode = DB::table('mfa_backup_codes')
            ->where('user_id', $user->id)
            ->where('code', $hashedCode)
            ->where('used', false)
            ->first();

        if ($backupCode) {
            DB::table('mfa_backup_codes')
                ->where('id', $backupCode->id)
                ->update(['used' => true]);
            return true;
        }
        return false;
    }

    protected function rememberDevice($user, Request $request)
    {
        // Validate device information
        if (!$this->isValidDeviceSignature($request)) {
            return null;
        }

        $deviceToken = Str::random(64);
        DB::table('mfa_remembered_devices')->insert([
            'user_id' => $user->id,
            'device_token' => Hash::make($deviceToken),
            'device_signature' => $this->generateDeviceSignature($request),
            'ip_address' => $request->ip(),
            'expires_at' => now()->addDays(30),
            'created_at' => now()
        ]);
        return $deviceToken;
    }

    protected function isValidDeviceSignature(Request $request): bool
    {
        // Add validation logic for user agent and IP
        $userAgent = $request->userAgent();
        $ipAddress = $request->ip();
        
        // Example validation rules
        return !empty($userAgent) 
            && strlen($userAgent) <= 255
            && filter_var($ipAddress, FILTER_VALIDATE_IP);
    }

    public function regenerateBackupCodes(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Delete old backup codes
            DB::table('mfa_backup_codes')
                ->where('user_id', $user->id)
                ->delete();

            // Generate new codes
            $codes = $this->generateBackupCodes($user);

            Log::info('Backup codes regenerated', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'message' => 'Backup codes regenerated successfully',
                'backupCodes' => $codes
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to regenerate backup codes', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to regenerate backup codes'], 500);
        }
    }

    public function downloadQR(Request $request)
    {
        $google2fa = app('pragmarx.google2fa');
        $user = Auth::user();
        $secretKey = $request->query('key', $user->google2fa_secret);

        $QRImage = $google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secretKey
        );
        // Decode the base64 inline QR code and serve it as an image
        $qrContent = base64_decode(substr($QRImage, strpos($QRImage, ',') + 1));

        return response($qrContent)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="mfa-qr-code.png"');
    }

    /**
 * Get MFA status for authenticated user
 */
public function getStatus()
{
    $user = Auth::user();
    
    return response()->json([
        'mfaEnabled' => $user->is_mfa_enabled,
        'mfaVerified' => !empty($user->mfa_verified_at)
    ]);
}

}