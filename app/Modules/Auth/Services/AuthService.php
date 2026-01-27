<?php

namespace App\Modules\Auth\Services;

use App\Models\MstAccount;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function attemptLogin(string $username, string $password): ?MstAccount
    {
        $account = MstAccount::query()
            ->with('employee')
            ->where('Username', $username)
            ->where('IsActive', true)
            ->first();

        if (!$account) {
            return null;
        }

        return $this->passwordMatches($account, $password) ? $account : null;
    }

    private function passwordMatches(MstAccount $account, string $password): bool
    {
        $hash = (string) $account->PasswordHash;
        if ($hash === '') {
            return false;
        }

        if (!Str::startsWith($hash, ['$2y$', '$2b$', '$argon2'])) {
            return false;
        }

        return Hash::check($password, $hash);
    }
}
