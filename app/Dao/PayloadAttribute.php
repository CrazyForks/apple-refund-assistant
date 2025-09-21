<?php

namespace App\Dao;

use App\Models\App;
use Illuminate\Database\Eloquent\Model;
use Readdle\AppStoreServerAPI\ResponseBodyV2;
use Readdle\AppStoreServerAPI\TransactionInfo;

abstract class PayloadAttribute
{

    protected function formatPrice(?int $price)
    {
        if (is_null($price)) {
            return 0;
        }

        return $price / 100;
    }
    protected function fixTs(int $ts)
    {
        return $ts / 1000;
    }
}
