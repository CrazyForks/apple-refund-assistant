<?php

namespace App\Services;

use App\Dao\AppleUserDao;
use App\Models\App;
use App\Models\AppleUser;
use App\Models\TransactionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\CacheInterface;
use Readdle\AppStoreServerAPI\AppStoreServerAPI;
use Readdle\AppStoreServerAPI\Exception\AppStoreServerAPIException;
use Readdle\AppStoreServerAPI\Exception\AppStoreServerNotificationException;
use Readdle\AppStoreServerAPI\Exception\WrongEnvironmentException;
use Readdle\AppStoreServerAPI\RequestBody\ConsumptionRequestBody;
use Readdle\AppStoreServerAPI\ResponseBodyV2;
use Readdle\AppStoreServerAPI\Util\Helper;

class AmountPriceService
{
    protected Repository $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }


   public function toDollar(string $currency, float $price) : float
   {
       $dollarMap = $this->cacheGetDollarData();
       $rate = $dollarMap[strtoupper($currency)] ?? 1;
       return bcdiv(bcdiv($price, 100, 2),  $rate, 2);
   }

   protected function cacheGetDollarData()
   {
       return $this->cache->remember('dollar_rate_data', Carbon::now()->endOfDay(), function () {
           return $this->requestDollarData();
       });
   }

   protected function requestDollarData()
   {
       try{
           $jsonData = Http::timeout(30)
               ->get('https://open.er-api.com/v6/latest/USD')
               ->json();
       } catch (\Exception $e) {
           Log::error($e);
           $jsonData = json_decode(file_get_contents(storage_path('data/dollar.json')), true);
       }

       return $jsonData['rates'] ?? [];
   }


}
