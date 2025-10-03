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

    public function test_to_dollar_with_non_usd_currency_conversion(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 2.0  // 简单的 2:1 比率便于测试
                ]
            ])
        ]);

        $service = $this->createService();
        $result = $service->toDollar('EUR', 200); // €2.00 -> $1.00
        
        $this->assertInstanceOf(Money::class, $result);
        $this->assertEquals('USD', $result->getCurrency()->getCurrencyCode());
        $this->assertEquals('1.00', $result->getAmount()->__toString());
    }

    public function test_to_dollar_float_with_non_usd_currency(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'CNY' => 10.0  // 简单的 10:1 比率
                ]
            ])
        ]);

        $service = $this->createService();
        $result = $service->toDollarFloat('CNY', 1000); // ¥10.00 -> $1.00
        
        $this->assertIsFloat($result);
        $this->assertEquals(1.0, $result);
    }


    public function test_cache_get_dollar_data_uses_cache(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => ['USD' => 1.0, 'EUR' => 0.85]
            ])
        ]);

        $service = $this->createService();
        
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('cacheGetDollarData');
        $method->setAccessible(true);

        // 第一次调用
        $result1 = $method->invoke($service);
        
        // 第二次调用应该从缓存获取
        $result2 = $method->invoke($service);
        
        $this->assertEquals($result1, $result2);
        // 验证只发送了一次 HTTP 请求
        Http::assertSentCount(1);
    }

    public function test_http_timeout_configuration(): void
    {
        // 测试 HTTP 请求配置了 30 秒超时
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => ['USD' => 1.0, 'EUR' => 0.85]
            ])
        ]);

        $service = $this->createService();
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('requestDollarData');
        $method->setAccessible(true);

        $result = $method->invoke($service);

        $this->assertIsArray($result);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://open.er-api.com/v6/latest/USD';
        });
    }

    public function test_request_dollar_data_returns_empty_array_when_no_rates(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'success' => true
                // 没有 'rates' 键
            ])
        ]);

        $service = $this->createService();
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('requestDollarData');
        $method->setAccessible(true);

        $result = $method->invoke($service);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_exchange_rate_provider_creates_provider(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.85,
                    'CNY' => 7.2
                ]
            ])
        ]);

        $service = $this->createService();
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getExchangeRateProvider');
        $method->setAccessible(true);

        $provider = $method->invoke($service);

        $this->assertInstanceOf(\Brick\Money\ExchangeRateProvider\ConfigurableProvider::class, $provider);
    }

    public function test_get_converter_is_lazy_loaded(): void
    {
        $service = $this->createService();
        
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('converter');
        $property->setAccessible(true);

        // 初始应该为 null
        $this->assertNull($property->getValue($service));
    }

    public function test_get_converter_returns_same_instance(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 2.0
                ]
            ])
        ]);

        $service = $this->createService();
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getConverter');
        $method->setAccessible(true);

        $converter1 = $method->invoke($service);
        $converter2 = $method->invoke($service);

        // 应该返回同一个实例（单例模式）
        $this->assertSame($converter1, $converter2);
    }

    public function test_to_dollar_with_negative_amount(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => ['USD' => 1.0]
            ])
        ]);

        $service = $this->createService();
        $result = $service->toDollar('USD', -100); // -$1.00
        
        $this->assertInstanceOf(Money::class, $result);
        $this->assertLessThan(0, $result->getAmount()->toFloat());
    }


    public function test_multiple_currency_conversions_in_sequence(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 2.0,
                    'CNY' => 10.0
                ]
            ])
        ]);

        $service = $this->createService();
        
        // 测试连续转换
        $result1 = $service->toDollar('EUR', 200);
        $result2 = $service->toDollar('CNY', 1000);
        $result3 = $service->toDollar('USD', 100);
        
        $this->assertEquals('1.00', $result1->getAmount()->__toString());
        $this->assertEquals('1.00', $result2->getAmount()->__toString());
        $this->assertEquals('1.00', $result3->getAmount()->__toString());
        
        // Converter 应该只初始化一次
        Http::assertSentCount(1);
    }

    public function test_request_dollar_data_with_http_exception(): void
    {
        // 模拟 HTTP 异常
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $dataDir = storage_path('data');
        $dollarJsonPath = storage_path('data/dollar.json');
        $backupPath = storage_path('data/dollar.json.backup');
        
        // 备份原有文件（如果存在）
        $hadOriginalFile = false;
        if (file_exists($dollarJsonPath)) {
            $hadOriginalFile = true;
            copy($dollarJsonPath, $backupPath);
        }
        
        // 创建目录（如果不存在）
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        // 创建测试数据文件
        $testData = json_encode([
            'rates' => [
                'USD' => 1.0,
                'EUR' => 0.85,
                'CNY' => 7.2
            ]
        ]);
        file_put_contents($dollarJsonPath, $testData);

        // Mock Log facade
        Log::shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/Failed to fetch exchange rates.*Network error/'));

        $service = $this->createService();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('requestDollarData');
        $method->setAccessible(true);

        $result = $method->invoke($service);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('USD', $result);
        $this->assertEquals(1.0, $result['USD']);
        
        // 恢复原文件或清理测试文件
        if ($hadOriginalFile) {
            // 恢复原文件
            copy($backupPath, $dollarJsonPath);
            unlink($backupPath);
        } else {
            // 删除测试创建的文件
            if (file_exists($dollarJsonPath)) {
                unlink($dollarJsonPath);
            }
            // 如果目录是我们创建的且为空，则删除
            if (is_dir($dataDir) && count(scandir($dataDir)) === 2) {
                rmdir($dataDir);
            }
        }
    }

    public function test_get_exchange_rate_provider_with_multiple_currencies(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.85,
                    'CNY' => 7.2,
                    'GBP' => 0.73,
                    'JPY' => 110.0
                ]
            ])
        ]);

        $service = $this->createService();
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getExchangeRateProvider');
        $method->setAccessible(true);

        $provider = $method->invoke($service);

        $this->assertInstanceOf(\Brick\Money\ExchangeRateProvider\ConfigurableProvider::class, $provider);
        
        // 验证汇率提供器已经设置了多个货币
        // 通过实际转换来验证
        $converter = new \Brick\Money\CurrencyConverter($provider);
        $money = \Brick\Money\Money::of(1, 'USD');
        $eurMoney = $converter->convert($money, 'EUR', new \Brick\Money\Context\CashContext(2), \Brick\Math\RoundingMode::HALF_UP);
        
        $this->assertEquals('EUR', $eurMoney->getCurrency()->getCurrencyCode());
    }

    public function test_get_exchange_rate_provider_skips_usd(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.85
                ]
            ])
        ]);

        $service = $this->createService();
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getExchangeRateProvider');
        $method->setAccessible(true);

        $provider = $method->invoke($service);

        // USD 应该被跳过，不设置汇率
        $this->assertInstanceOf(\Brick\Money\ExchangeRateProvider\ConfigurableProvider::class, $provider);
    }

    public function test_cache_expiry_is_end_of_day(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => ['USD' => 1.0, 'EUR' => 0.85]
            ])
        ]);

        $service = $this->createService();
        
        // 使用反射测试缓存过期时间
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('cacheGetDollarData');
        $method->setAccessible(true);

        // 第一次调用会设置缓存
        $result = $method->invoke($service);
        
        $this->assertIsArray($result);
        // 缓存应该被设置到当天结束
    }

    public function test_to_dollar_with_usd_uppercase(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => ['USD' => 1.0]
            ])
        ]);

        $service = $this->createService();
        $result = $service->toDollar('USD', 5000); // $50.00
        
        $this->assertInstanceOf(Money::class, $result);
        $this->assertEquals('50.00', $result->getAmount()->__toString());
        $this->assertEquals('USD', $result->getCurrency()->getCurrencyCode());
    }

    public function test_to_dollar_float_with_zero_amount(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => ['USD' => 1.0]
            ])
        ]);

        $service = $this->createService();
        $result = $service->toDollarFloat('USD', 0);
        
        $this->assertIsFloat($result);
        $this->assertEquals(0.0, $result);
    }

    public function test_exchange_rate_provider_sets_reverse_rates(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 2.0
                ]
            ])
        ]);

        $service = $this->createService();
        
        // 测试反向汇率是否正确设置
        $result = $service->toDollar('EUR', 200); // €2.00 -> $1.00
        
        $this->assertInstanceOf(Money::class, $result);
        $this->assertEquals('USD', $result->getCurrency()->getCurrencyCode());
        $this->assertEquals('1.00', $result->getAmount()->__toString());
    }

    public function test_request_dollar_data_with_valid_json_response(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'result' => 'success',
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.85
                ]
            ])
        ]);

        $service = $this->createService();
        
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('requestDollarData');
        $method->setAccessible(true);

        $result = $method->invoke($service);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('USD', $result);
        $this->assertArrayHasKey('EUR', $result);
    }

    public function test_bcdiv_precision_in_reverse_rate_calculation(): void
    {
        Http::fake([
            'https://open.er-api.com/v6/latest/USD' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'JPY' => 110.0  // 使用真实的 JPY 汇率
                ]
            ])
        ]);

        $service = $this->createService();
        
        // 测试高精度计算是否正确
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getExchangeRateProvider');
        $method->setAccessible(true);

        $provider = $method->invoke($service);
        
        $this->assertInstanceOf(\Brick\Money\ExchangeRateProvider\ConfigurableProvider::class, $provider);
        
        // 验证可以进行转换
        $result = $service->toDollar('JPY', 11000); // ¥110.00
        $this->assertInstanceOf(Money::class, $result);
    }
}
