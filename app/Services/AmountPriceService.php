<?php

namespace App\Services;

use Brick\Math\RoundingMode;
use Brick\Money\Context\CashContext;
use Brick\Money\CurrencyConverter;
use Brick\Money\ExchangeRateProvider\BaseCurrencyProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmountPriceService
{
    protected Repository $cache;
    protected ?CurrencyConverter $converter = null;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
        // 延迟初始化 converter，避免构造函数中触发 HTTP 请求
    }

    /**
     * 获取货币转换器（懒加载）
     */
    protected function getConverter(): CurrencyConverter
    {
        if ($this->converter === null) {
            $this->converter = new CurrencyConverter($this->getExchangeRateProvider());
        }
        return $this->converter;
    }

    /**
     * 将价格转换为美元 Money 对象
     * 
     * @param string $currency 货币代码 (如 'CNY', 'EUR')
     * @param int $priceInCents 价格（以分为单位）
     * @return Money 美元金额对象
     */
    public function toDollar(string $currency, int $priceInCents): Money
    {
        // 创建原始货币的 Money 对象（以分为单位）
        $originalMoney = Money::ofMinor($priceInCents, $currency, new CashContext(2));
        
        // 如果已经是美元，直接返回
        if (strtoupper($currency) === 'USD') {
            return $originalMoney;
        }
        
        // 转换为美元，使用四舍五入
        return $this->getConverter()->convert(
            $originalMoney, 
            'USD', 
            new CashContext(2), 
            RoundingMode::HALF_UP
        );
    }

    /**
     * 将价格转换为美元浮点数（向后兼容）
     * 
     * @param string $currency 货币代码
     * @param int $priceInCents 价格（以分为单位）
     * @return float 美元金额
     */
    public function toDollarFloat(string $currency, int $priceInCents): float
    {
        return $this->toDollar($currency, $priceInCents)->getAmount()->toFloat();
    }

    /**
     * 获取汇率提供器
     */
    protected function getExchangeRateProvider(): ConfigurableProvider
    {
        $provider = new ConfigurableProvider();
        $exchangeRates = $this->cacheGetDollarData();
        
        // 设置汇率（相对于美元）
        // brick/money 的 setExchangeRate(sourceCurrency, targetCurrency, rate) 
        // 表示：1 sourceCurrency = rate targetCurrency
        foreach ($exchangeRates as $currency => $rate) {
            if ($currency !== 'USD') {
                // API 返回的汇率：1 USD = $rate 其他货币
                // 例如：1 USD = 7.2 CNY
                $provider->setExchangeRate('USD', $currency, (string) $rate);
                
                // 反向汇率：1 其他货币 = (1/$rate) USD
                // 例如：1 CNY = (1/7.2) USD = 0.1389 USD
                // 使用更高精度避免舍入错误
                $reverseRate = bcdiv('1', (string) $rate, 15);
                $provider->setExchangeRate($currency, 'USD', $reverseRate);
            }
        }
        
        return $provider;
    }

    protected function cacheGetDollarData(): array
    {
        return $this->cache->remember('dollar_rate_data', Carbon::now()->endOfDay(), function () {
            return $this->requestDollarData();
        });
    }

    protected function requestDollarData(): array
    {
        try {
            $jsonData = Http::timeout(30)
                ->get('https://open.er-api.com/v6/latest/USD')
                ->json();
        } catch (\Exception $e) {
            Log::error('Failed to fetch exchange rates: ' . $e->getMessage());
            $jsonData = json_decode(file_get_contents(storage_path('data/dollar.json')), true);
        }

        return $jsonData['rates'] ?? [];
    }
}
