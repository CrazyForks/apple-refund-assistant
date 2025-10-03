<?php

namespace Tests\Unit\Services;

use App\Services\AmountPriceService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AmountPriceServiceTest extends TestCase
{
    /**
     * 创建服务实例（懒加载）
     */
    protected function createService(): AmountPriceService
    {
        return new AmountPriceService(Cache::store('array'));
    }

    public function test_to_dollar_returns_money_object_type(): void
    {
        // 在测试方法内部创建，并且 Mock 在创建之前设置
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => ['USD' => 1.0]
            ])
        ]);

        $service = $this->createService();
        $result = $service->toDollar('USD', 100);
        
        $this->assertInstanceOf(Money::class, $result);
        $this->assertEquals('USD', $result->getCurrency()->getCurrencyCode());
    }

    public function test_to_dollar_float_returns_float_type(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => ['USD' => 1.0, 'CNY' => 7.2]
            ])
        ]);

        $service = $this->createService();
        $result = $service->toDollarFloat('USD', 250);
        
        $this->assertIsFloat($result);
        $this->assertGreaterThan(0, $result);
    }

    public function test_to_dollar_with_zero_amount(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => ['USD' => 1.0]
            ])
        ]);

        $service = $this->createService();
        $result = $service->toDollar('USD', 0);
        
        $this->assertInstanceOf(Money::class, $result);
        $this->assertEquals('0.00', $result->getAmount()->__toString());
    }

    public function test_request_dollar_data_success(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.85,
                    'CNY' => 7.2
                ]
            ], 200)
        ]);

        $service = $this->createService();
        
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('requestDollarData');
        $method->setAccessible(true);

        $result = $method->invoke($service);

        $this->assertIsArray($result);
        $this->assertEquals(1.0, $result['USD']);
        $this->assertEquals(0.85, $result['EUR']);
        $this->assertEquals(7.2, $result['CNY']);
    }
}
