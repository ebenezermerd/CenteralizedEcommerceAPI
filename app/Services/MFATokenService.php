<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MFATokenService
{
    const TOKEN_PREFIX = 'mfa_temp_';
    const TOKEN_EXPIRY = 600; // 10 minutes

    public function generateTempToken(int $userId): string
    {
        $token = Str::random(64);
        $key = self::TOKEN_PREFIX . $token;
        
        $data = [
            'user_id' => $userId,
            'created_at' => now(),
        ];

        Cache::store('database')->put($key, $data, Carbon::now()->addSeconds(self::TOKEN_EXPIRY));
        
        
        Log::info('MFA temporary token generated at MFA Token Service provider', [
            'user_id' => $userId,
            'token_key' => $key
        ]);
        
        return $token;
    }

    public function validateTempToken(string $token): ?int
    {
        $key = self::TOKEN_PREFIX . $token;
        
        Log::info('Attempting to validate MFA token in MFA Token Service provider', [
            'token_key' => $key,
            'token_length' => strlen($token)
        ]);
        
        $data = Cache::store('database')->get($key);
        
        if (!$data) {
            Log::warning('Invalid MFA temporary token validation attempt from the MFA service provider', [
                'token_key' => $key,
                'ip' => request()->ip(),
                'cache_hit' => false
            ]);
            return null;
        }

        // Check token age
        $age = now()->diffInSeconds($data['created_at']);
        Log::info('Token age check in MFA Token validation from MFA service provider', [
            'token_age_seconds' => $age,
            'max_age' => self::TOKEN_EXPIRY,
            'data' => $data
        ]);

        // Invalidate after use
        // Cache::forget($key);
        
        Log::info('MFA temporary token validated successfully in MFA token service provider', [
            'user_id' => $data['user_id'],
            'token_age' => $age,
            'ip' => request()->ip()
        ]);
        
        return $data['user_id'];
    }
}
