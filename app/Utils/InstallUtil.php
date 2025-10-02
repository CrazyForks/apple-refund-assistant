<?php

namespace App\Utils;

use Carbon\Carbon;

class InstallUtil
{
    public static function canInstall(): bool
    {
        $installAt = config('app.installed_at');
        if (is_null($installAt)) {
            return true;
        }

        // 60s sleep
        return $installAt + 60 > Carbon::now()->unix();
    }
}
