<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class BackupCodeService
{
    public function generateCodes(int $userId, int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $code = strtoupper(Str::random(10));
            $salt = Str::random(16);
            $codes[] = $code;
            
            DB::table('mfa_backup_codes')->insert([
                'user_id' => $userId,
                'code' => Hash::make($code . $salt),
                'salt' => $salt,
                'created_at' => now()
            ]);
        }
        return $codes;
    }

    public function verifyCode(int $userId, string $code): bool
    {
        $backupCodes = DB::table('mfa_backup_codes')
            ->where('user_id', $userId)
            ->where('used', false)
            ->get();

        foreach ($backupCodes as $backupCode) {
            if (Hash::check($code . $backupCode->salt, $backupCode->code)) {
                DB::table('mfa_backup_codes')
                    ->where('id', $backupCode->id)
                    ->update(['used' => true]);
                return true;
            }
        }
        return false;
    }
}
