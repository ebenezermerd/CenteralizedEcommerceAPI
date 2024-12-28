<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MFATokenService
{
    const TOKEN_PREFIX = 'mfa_temp_';
    const TOKEN_EXPIRY = 300; // 5 minutes

    public function generateTempToken(int $userId): string
    {
        $token = Str::random(64);
        $key = self::TOKEN_PREFIX . $token;
        
        Cache::put($key, [
            'user_id' => $userId,
            'created_at' => now(),
        ], Carbon::now()->addSeconds(self::TOKEN_EXPIRY));
        
        return $token;
    }

    public function validateTempToken(string $token): ?int
    {
        $key = self::TOKEN_PREFIX . $token;
        $data = Cache::get($key);
        
        if (!$data) {
            return null;
        }

        // Invalidate after use
        Cache::forget($key);
        
        return $data['user_id'];
    }
}
