<?php

namespace Tests\Unit\Services;

use App\Services\AmountPriceService;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class AmountPriceServiceTest extends TestCase
{
    protected $cacheRepository;
    protected $amountPriceService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheRepository = Mockery::mock(Repository::class);
        $this->amountPriceService = new AmountPriceService($this->cacheRepository);
    }

    public function test_to_dollar_with_cached_rates(): void
    {
        // Mock cache data
        $mockRates = [
            'USD' => 1,
            'EUR' => 0.85,
            'GBP' => 0.73,
            'CNY' => 7.2,
        ];

        $this->cacheRepository
            ->shouldReceive('remember')
            ->times(3) // Will be called 3 times for 3 different conversions
            ->andReturn($mockRates);

        // Test USD conversion
        $result = $this->amountPriceService->toDollar('USD', 199);
        $this->assertEquals(1.99, $result);

        // Test EUR conversion
        $result = $this->amountPriceService->toDollar('EUR', 199);
        $this->assertEquals(2.34, $result); // 199/100/0.85 = 2.34

        // Test unknown currency (should default to rate 1)
        $result = $this->amountPriceService->toDollar('UNKNOWN', 199);
        $this->assertEquals(1.99, $result);
    }

    public function test_to_dollar_with_lowercase_currency(): void
    {
        $mockRates = ['USD' => 1, 'EUR' => 0.85];
        
        $this->cacheRepository
            ->shouldReceive('remember')
            ->once()
            ->andReturn($mockRates);

        // Test lowercase currency code
        $result = $this->amountPriceService->toDollar('eur', 170);
        $this->assertEquals(2.00, $result); // 170/100/0.85 = 2.00
    }

    public function test_request_dollar_data_success(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => [
                    'USD' => 1,
                    'EUR' => 0.85,
                    'GBP' => 0.73,
                ]
            ], 200)
        ]);

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->amountPriceService);
        $method = $reflection->getMethod('requestDollarData');
        $method->setAccessible(true);

        $result = $method->invoke($this->amountPriceService);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['USD']);
        $this->assertEquals(0.85, $result['EUR']);
        $this->assertEquals(0.73, $result['GBP']);
    }
    
    public function test_cache_get_dollar_data(): void
    {
        $mockRates = ['USD' => 1, 'EUR' => 0.85];
        
        $this->cacheRepository
            ->shouldReceive('remember')
            ->once()
            ->with('dollar_rate_data', \Mockery::type(\Carbon\Carbon::class), \Mockery::type('callable'))
            ->andReturn($mockRates);

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->amountPriceService);
        $method = $reflection->getMethod('cacheGetDollarData');
        $method->setAccessible(true);

        $result = $method->invoke($this->amountPriceService);

        $this->assertEquals($mockRates, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}