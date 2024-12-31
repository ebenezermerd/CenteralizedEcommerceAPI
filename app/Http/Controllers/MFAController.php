<?php

namespace App\Http\Controllers;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
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
use Tymon\JWTAuth\Facades\JWTAuth;


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

        // Generate QR code as PNG
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new ImagickImageBackEnd('png')
        );
        $writer = new Writer($renderer);
        $qrCodePng = $writer->writeString($google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secretKey
));

            // Convert to base64
        $base64QrCode = 'data:image/png;base64,' . base64_encode($qrCodePng);



            Activity::create([
                'log_name' => 'mfa',
                'description' => 'MFA setup completed',
                'causer_id' => $user->id,
                'causer_type' => get_class($user),
                'properties' => ['setup_completed' => true]
            ]);

            Log::info('MFA setup completed in MFA controller', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

           // Generate backup codes
        $backupCodes = $this->generateBackupCodes($user);


        return response()->json([
            'secret' => $secretKey,
            'qrImage' => [
                'dataUrl' => $base64QrCode,
                'base64' => base64_encode($qrCodePng),
                'downloadUrl' => '/api/auth/mfa/download-qr?key=' . $secretKey
            ],
            'backupCodes' => $backupCodes,
            'message' => 'MFA setup initialized successfully'
        ], 200);
        } catch (\Exception $e) {
            Log::error('MFA setup failed in MFA controller', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);
            return response()->json(['error' => 'MFA setup failed'], 500);
        }
    }

    public function verify(Request $request)
    {
        Log::info('Starting MFA verification process in controller', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $request->validate([
            'oneTimePassword' => 'required|string|size:6',
            'tempToken' => 'required|string'
        ]);

        try {
            // Validate temp token and get user
            Log::info('Validating temporary token in controller', [
                'token_length' => strlen($request->tempToken)
            ]);
            
            $userId = app(MFATokenService::class)->validateTempToken($request->tempToken);
            if (!$userId) {
                Log::warning('Invalid temporary token provided in controller', [
                    'token_prefix' => substr($request->tempToken, 0, 10) . '...',
                    'ip' => $request->ip()
                ]);
                return response()->json(['error' => 'Invalid temporary token'], 401);
            }
            
            Log::info('Temporary token validated successfully in controller', ['user_id' => $userId]);
            
            $user = User::find($userId);
            if (!$user) {
                Log::error('User not found after token validation in controller', ['user_id' => $userId]);
                return response()->json(['error' => 'User not found'], 404);
            }

            // Rate limiting check
            $key = 'mfa_attempts_' . $user->id;
            $attempts = Cache::get($key, 0);
            
            Log::info('Checking rate limiting', [
                'user_id' => $userId,
                'current_attempts' => $attempts,
                'max_attempts' => $this->maxAttempts
            ]);
            
            if ($attempts >= $this->maxAttempts) {
                Log::warning('Rate limit exceeded for MFA verification in controller', [
                    'user_id' => $userId,
                    'attempts' => $attempts
                ]);
                return response()->json([
                    'error' => 'Too many attempts',
                    'message' => "Please try again after {$this->decayMinutes} minutes"
                ], 429);
            }

            // Verify OTP
            Log::info('Attempting to verify OTP code', [
                'user_id' => $userId,
                'otp_length' => strlen($request->oneTimePassword)
            ]);

            $isValidOtp = $this->verifyCode($user, $request->oneTimePassword);
            //  $isValidBackup = $this->verifyBackupCode($user, $request->oneTimePassword);

            Log::info('OTP verification result', [
                'user_id' => $userId,
                'otp_valid' => $isValidOtp,
                // 'backup_valid' => $isValidBackup
            ]);

            if ($isValidOtp) {
                
                Cache::forget($key);
                // Generate new JWT tokens
                $token = JWTAuth::fromUser($user);
                $refreshToken = JWTAuth::fromUser($user);

                // Update MFA verification timestamp
                $user->mfa_verified_at = now();
                $user->save();

                return response()->json([
                    'data' => [
                        'status' => 'success',
                        'message' => 'MFA verification successful',
                        'accessToken' => $token,
                        'refreshToken' => $refreshToken,
                        'role' => $user->getRoleNames()->first(),
                        'expires_in' => auth()->factory()->getTTL() * 60
                    ]
                ],201);
            }

            // Log failed attempt
            Log::warning('Failed MFA verification attempt', [
                'user_id' => $userId,
                'attempts' => $attempts + 1,
                'remaining_attempts' => $this->maxAttempts - ($attempts + 1)
            ]);

            Cache::put($key, $attempts + 1, now()->addMinutes($this->decayMinutes));

            return response()->json([
                'error' => 'Invalid code',
                'attemptsRemaining' => $this->maxAttempts - ($attempts + 1)
            ], 401);

        } catch (\Exception $e) {
            Log::error('MFA verification error', [
                'error' => $e->getMessage(),
                'user_id' => $userId ?? null,
                'ip' => $request->ip(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'MFA verification failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

       protected function verifyCode($user, $code)
    {
        $google2fa = app('pragmarx.google2fa');
        $result = $google2fa->verifyKey($user->mfa_secret, $code);
        
        Log::info('Google2FA verification attempt', [
            'user_id' => $user->id,
            'success' => $result,
            'code_length' => strlen($code)
        ]);
        
        return $result;
    }

    protected function verifyBackupCode($user, $code)
    {
        // Retrieve all unused backup codes for the user
        $backupCodes = DB::table('mfa_backup_codes')
            ->where('user_id', $user->id)
            ->where('used', false)
            ->get();
    
        foreach ($backupCodes as $backupCode) {
            // Hash the provided code with the stored salt
            $hashedCode = hash('sha256', $code . $backupCode->salt);
    
            // Check if the hash matches the stored hash
            if ($hashedCode === $backupCode->code) {
                // Mark the backup code as used
                DB::table('mfa_backup_codes')
                    ->where('id', $backupCode->id)
                    ->update(['used' => true]);
    
                return true; // Code is valid and marked as used
            }
        }
    
        return false; // No matching backup code found
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