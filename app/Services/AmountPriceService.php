<?php

declare(strict_types=1);

namespace App\Services;

use Brick\Math\RoundingMode;
use Brick\Money\Context\CashContext;
use Brick\Money\CurrencyConverter;
use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmountPriceService
{
    protected ?CurrencyConverter $converter = null;

    public function __construct(
        protected Repository $cache,
    ) {
    }

    /**
     * Get currency converter (lazy loading)
     */
    protected function getConverter(): CurrencyConverter
    {
        if ($this->converter === null) {
            $this->converter = new CurrencyConverter($this->getExchangeRateProvider());
        }
        return $this->converter;
    }

    /**
     * Convert price to USD Money object
     *
     * @param string $currency Currency code (e.g., 'CNY', 'EUR')
     * @param int $priceInCents Price in cents
     * @return Money USD amount object
     * @throws CurrencyConversionException
     */
    public function toDollar(string $currency, int $priceInCents): Money
    {
        // Create Money object for original currency (in cents)
        $originalMoney = Money::ofMinor($priceInCents, $currency, new CashContext(2));

        // If already USD, return directly
        if (strtoupper($currency) === 'USD') {
            return $originalMoney;
        }

        // Convert to USD with rounding
        return $this->getConverter()->convert(
            $originalMoney,
            'USD',
            new CashContext(2),
            RoundingMode::HALF_UP
        );
    }

    /**
     * Convert price to USD float (backward compatible)
     *
     * @param string $currency Currency code
     * @param int $priceInCents Price in cents
     * @return float USD amount
     */
    public function toDollarFloat(string $currency, int $priceInCents): float
    {
        return $this->toDollar($currency, $priceInCents)->getAmount()->toFloat();
    }

    /**
     * Get exchange rate provider
     */
    protected function getExchangeRateProvider(): ConfigurableProvider
    {
        $provider = new ConfigurableProvider();
        $exchangeRates = $this->cacheGetDollarData();

        // Set exchange rates (relative to USD)
        // brick/money's setExchangeRate(sourceCurrency, targetCurrency, rate)
        // means: 1 sourceCurrency = rate targetCurrency
        foreach ($exchangeRates as $currency => $rate) {
            if ($currency !== 'USD') {
                // API returns rate: 1 USD = $rate other currency
                // Example: 1 USD = 7.2 CNY
                $provider->setExchangeRate('USD', $currency, (string) $rate);

                // Reverse rate: 1 other currency = (1/$rate) USD
                // Example: 1 CNY = (1/7.2) USD = 0.1389 USD
                // Use higher precision to avoid rounding errors
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
