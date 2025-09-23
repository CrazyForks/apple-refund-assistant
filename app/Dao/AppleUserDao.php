<?php

namespace App\Dao;

use App\Models\App;
use App\Models\AppleUser;
use Illuminate\Database\Eloquent\Collection;

class AppleUserDao
{

    public function find(?string $appAccountToken, int $appId) : ?AppleUser
    {
        if (empty($appAccountToken)) {
            return null;
        }

        return AppleUser::query()
            ->where('app_account_token', $appAccountToken)
            ->where('app_id', $appId)
            ->first();
    }
}
