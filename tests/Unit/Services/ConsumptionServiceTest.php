<?php

namespace Tests\Unit\Services;

use App\Dao\AppleUserDao;
use App\Dao\AppDao;
use App\Dao\TransactionLogDao;
use App\Models\App;
use App\Models\AppleUser;
use App\Models\ConsumptionLog;
use App\Models\TransactionLog;
use App\Services\ConsumptionService;
use Carbon\Carbon;
use Readdle\AppStoreServerAPI\RequestBody\ConsumptionRequestBody;
use Tests\TestCase;
use Mockery;

class ConsumptionServiceTest extends TestCase
{
    protected $appleUserDao;
    protected $appDao;
    protected $transactionLogDao;
    protected $consumptionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->appleUserDao = Mockery::mock(AppleUserDao::class);
        $this->appDao = Mockery::mock(AppDao::class);
        $this->transactionLogDao = Mockery::mock(TransactionLogDao::class);
        $this->consumptionService = new ConsumptionService(
            $this->appleUserDao, 
            $this->appDao, 
            $this->transactionLogDao
        );
    }

    public function test_make_consumption_request_with_user(): void
    {
        $app = new App([
            'id' => 1,
            'sample_content_provided' => true,
        ]);

        $user = new AppleUser([
            'purchased_dollars' => 150.50,
            'refunded_dollars' => 25.00,
            'play_seconds' => 7200, // 2 hours
            'register_at' => Carbon::now()->subDays(45),
        ]);

        $transaction = new TransactionLog();
        $transaction->app_account_token = 'test-token';
        $transaction->expiration_date = Carbon::now()->addDays(30);

        $consumptionLog = new ConsumptionLog([
            'app_id' => 1,
            'app_account_token' => 'test-token',
            'original_transaction_id' => 'original-123',
            'transaction_id' => 'transaction-456'
        ]);
        $consumptionLog->setRelation('app', $app);

        $this->transactionLogDao
            ->shouldReceive('findTransactionByConsumption')
            ->with($consumptionLog)
            ->once()
            ->andReturn($transaction);

        $this->appleUserDao
            ->shouldReceive('find')
            ->with('test-token', 1)
            ->once()
            ->andReturn($user);

        $result = $this->consumptionService->makeConsumptionRequest($consumptionLog);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('accountTenure', $result);
        $this->assertArrayHasKey('appAccountToken', $result);
        $this->assertArrayHasKey('consumptionStatus', $result);
        $this->assertArrayHasKey('customerConsented', $result);
        $this->assertArrayHasKey('deliveryStatus', $result);
        $this->assertArrayHasKey('lifetimeDollarsPurchased', $result);
        $this->assertArrayHasKey('lifetimeDollarsRefunded', $result);
        $this->assertArrayHasKey('platform', $result);
        $this->assertArrayHasKey('playTime', $result);
        $this->assertArrayHasKey('refundPreference', $result);
        $this->assertArrayHasKey('sampleContentProvided', $result);
        $this->assertArrayHasKey('userStatus', $result);
        
        $this->assertEquals('test-token', $result['appAccountToken']);
        $this->assertTrue($result['customerConsented']);
        $this->assertTrue($result['sampleContentProvided']);
    }

    public function test_make_consumption_request_without_user(): void
    {
        $app = new App([
            'id' => 1,
            'sample_content_provided' => false,
        ]);

        $transaction = new TransactionLog();
        $transaction->app_account_token = 'test-token';
        $transaction->expiration_date = null;

        $consumptionLog = new ConsumptionLog([
            'app_id' => 1,
            'app_account_token' => 'test-token',
            'original_transaction_id' => 'original-123',
            'transaction_id' => 'transaction-456'
        ]);
        $consumptionLog->setRelation('app', $app);

        // Create a default user to avoid null type error
        $defaultUser = new AppleUser([
            'purchased_dollars' => 0,
            'refunded_dollars' => 0,
            'play_seconds' => null,
            'register_at' => null,
        ]);

        $this->transactionLogDao
            ->shouldReceive('findTransactionByConsumption')
            ->with($consumptionLog)
            ->once()
            ->andReturn($transaction);

        $this->appleUserDao
            ->shouldReceive('find')
            ->with('test-token', 1)
            ->once()
            ->andReturn($defaultUser);

        $result = $this->consumptionService->makeConsumptionRequest($consumptionLog);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('appAccountToken', $result);
        $this->assertArrayHasKey('sampleContentProvided', $result);
        
        $this->assertEquals('test-token', $result['appAccountToken']);
        $this->assertFalse($result['sampleContentProvided']);
    }

    public function test_play_time_calculations(): void
    {
        $reflection = new \ReflectionClass($this->consumptionService);
        $method = $reflection->getMethod('playTime');
        $method->setAccessible(true);

        // Test null user
        $this->assertEquals(ConsumptionRequestBody::PLAY_TIME__UNDECLARED, $method->invoke($this->consumptionService, null));

        // Test null play_seconds
        $user = new AppleUser(['play_seconds' => null]);
        $this->assertEquals(ConsumptionRequestBody::PLAY_TIME__UNDECLARED, $method->invoke($this->consumptionService, $user));

        // Test 5 minutes
        $user = new AppleUser(['play_seconds' => 300]);
        $this->assertEquals(ConsumptionRequestBody::PLAY_TIME__5_MINUTES, $method->invoke($this->consumptionService, $user));

        // Test 1 hour
        $user = new AppleUser(['play_seconds' => 3600]);
        $this->assertEquals(ConsumptionRequestBody::PLAY_TIME__1_HOUR, $method->invoke($this->consumptionService, $user));

        // Test 6 hours
        $user = new AppleUser(['play_seconds' => 21600]);
        $this->assertEquals(ConsumptionRequestBody::PLAY_TIME__6_HOURS, $method->invoke($this->consumptionService, $user));

        // Test 1 day
        $user = new AppleUser(['play_seconds' => 86400]);
        $this->assertEquals(ConsumptionRequestBody::PLAY_TIME__1_DAY, $method->invoke($this->consumptionService, $user));

        // Test 4 days
        $user = new AppleUser(['play_seconds' => 345600]);
        $this->assertEquals(ConsumptionRequestBody::PLAY_TIME__4_DAYS, $method->invoke($this->consumptionService, $user));

        // Test 16 days
        $user = new AppleUser(['play_seconds' => 1382400]);
        $this->assertEquals(ConsumptionRequestBody::PLAY_TIME__16_DAYS, $method->invoke($this->consumptionService, $user));

        // Test over 16 days
        $user = new AppleUser(['play_seconds' => 2000000]);
        $this->assertEquals(ConsumptionRequestBody::PLAY_TIME__OVER_16_DAYS, $method->invoke($this->consumptionService, $user));
    }

    public function test_lifetime_dollars_purchased(): void
    {
        $reflection = new \ReflectionClass($this->consumptionService);
        $method = $reflection->getMethod('lifetimeDollarsPurchased');
        $method->setAccessible(true);

        // Test null user
        $this->assertEquals(ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__0, $method->invoke($this->consumptionService, null));

        // Test $0
        $user = new AppleUser(['purchased_dollars' => 0]);
        $this->assertEquals(ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__0, $method->invoke($this->consumptionService, $user));

        // Test $25
        $user = new AppleUser(['purchased_dollars' => 25]);
        $this->assertEquals(ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__50, $method->invoke($this->consumptionService, $user));

        // Test $75
        $user = new AppleUser(['purchased_dollars' => 75]);
        $this->assertEquals(ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__100, $method->invoke($this->consumptionService, $user));

        // Test $150
        $user = new AppleUser(['purchased_dollars' => 150]);
        $this->assertEquals(ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__500, $method->invoke($this->consumptionService, $user));

        // Test $750
        $user = new AppleUser(['purchased_dollars' => 750]);
        $this->assertEquals(ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__1000, $method->invoke($this->consumptionService, $user));

        // Test $1500
        $user = new AppleUser(['purchased_dollars' => 1500]);
        $this->assertEquals(ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__2000, $method->invoke($this->consumptionService, $user));

        // Test $2500
        $user = new AppleUser(['purchased_dollars' => 2500]);
        $this->assertEquals(ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__OVER_2000, $method->invoke($this->consumptionService, $user));
    }

    public function test_lifetime_dollars_refunded(): void
    {
        $reflection = new \ReflectionClass($this->consumptionService);
        $method = $reflection->getMethod('lifetimeDollarsRefunded');
        $method->setAccessible(true);

        // Test null user
        $this->assertEquals(ConsumptionRequestBody::LIFETIME_DOLLARS_REFUNDED__0, $method->invoke($this->consumptionService, null));

        // Test $0
        $user = new AppleUser(['refunded_dollars' => 0]);
        $this->assertEquals(ConsumptionRequestBody::LIFETIME_DOLLARS_REFUNDED__0, $method->invoke($this->consumptionService, $user));

        // Test $25
        $user = new AppleUser(['refunded_dollars' => 25]);
        $this->assertEquals(ConsumptionRequestBody::LIFETIME_DOLLARS_REFUNDED__50, $method->invoke($this->consumptionService, $user));

        // Test $2500
        $user = new AppleUser(['refunded_dollars' => 2500]);
        $this->assertEquals(ConsumptionRequestBody::LIFETIME_DOLLARS_REFUNDED__OVER_2000, $method->invoke($this->consumptionService, $user));
    }

    public function test_account_tenure(): void
    {
        $reflection = new \ReflectionClass($this->consumptionService);
        $method = $reflection->getMethod('accountTenure');
        $method->setAccessible(true);

        // Test null register_at
        $user = new AppleUser(['register_at' => null]);
        $this->assertEquals(ConsumptionRequestBody::ACCOUNT_TENURE__UNDECLARED, $method->invoke($this->consumptionService, $user));

        // Test 2 days
        $user = new AppleUser(['register_at' => Carbon::now()->subDays(2)]);
        $this->assertEquals(ConsumptionRequestBody::ACCOUNT_TENURE__3, $method->invoke($this->consumptionService, $user));

        // Test 5 days
        $user = new AppleUser(['register_at' => Carbon::now()->subDays(5)]);
        $this->assertEquals(ConsumptionRequestBody::ACCOUNT_TENURE__10, $method->invoke($this->consumptionService, $user));

        // Test 20 days
        $user = new AppleUser(['register_at' => Carbon::now()->subDays(20)]);
        $this->assertEquals(ConsumptionRequestBody::ACCOUNT_TENURE__30, $method->invoke($this->consumptionService, $user));

        // Test 60 days
        $user = new AppleUser(['register_at' => Carbon::now()->subDays(60)]);
        $this->assertEquals(ConsumptionRequestBody::ACCOUNT_TENURE__90, $method->invoke($this->consumptionService, $user));

        // Test 120 days
        $user = new AppleUser(['register_at' => Carbon::now()->subDays(120)]);
        $this->assertEquals(ConsumptionRequestBody::ACCOUNT_TENURE__180, $method->invoke($this->consumptionService, $user));

        // Test 200 days
        $user = new AppleUser(['register_at' => Carbon::now()->subDays(200)]);
        $this->assertEquals(ConsumptionRequestBody::ACCOUNT_TENURE__365, $method->invoke($this->consumptionService, $user));

        // Test 400 days
        $user = new AppleUser(['register_at' => Carbon::now()->subDays(400)]);
        $this->assertEquals(ConsumptionRequestBody::ACCOUNT_TENURE__OVER_365, $method->invoke($this->consumptionService, $user));
    }

    public function test_consumption_status(): void
    {
        $reflection = new \ReflectionClass($this->consumptionService);
        $method = $reflection->getMethod('consumptionStatus');
        $method->setAccessible(true);

        // Test null expiration_date
        $transaction = new TransactionLog();
        $transaction->expiration_date = null;
        $this->assertEquals(ConsumptionRequestBody::CONSUMPTION_STATUS__UNDECLARED, $method->invoke($this->consumptionService, $transaction));

        // Test expired
        $transaction = new TransactionLog();
        $transaction->expiration_date = Carbon::now()->subDay();
        $this->assertEquals(ConsumptionRequestBody::CONSUMPTION_STATUS__FULLY_CONSUMED, $method->invoke($this->consumptionService, $transaction));

        // Test not expired
        $transaction = new TransactionLog();
        $transaction->expiration_date = Carbon::now()->addDay();
        $this->assertEquals(ConsumptionRequestBody::CONSUMPTION_STATUS__PARTIALLY_CONSUMED, $method->invoke($this->consumptionService, $transaction));
    }

    public function test_refund_preference(): void
    {
        $reflection = new \ReflectionClass($this->consumptionService);
        $method = $reflection->getMethod('refundPreference');
        $method->setAccessible(true);

        // Test null expiration_date
        $transaction = new TransactionLog();
        $transaction->expiration_date = null;
        $this->assertEquals(ConsumptionRequestBody::REFUND_PREFERENCE__UNDECLARED, $method->invoke($this->consumptionService, $transaction));

        // Test expired
        $transaction = new TransactionLog();
        $transaction->expiration_date = Carbon::now()->subDay();
        $this->assertEquals(ConsumptionRequestBody::REFUND_PREFERENCE__DECLINE, $method->invoke($this->consumptionService, $transaction));

        // Test not expired
        $transaction = new TransactionLog();
        $transaction->expiration_date = Carbon::now()->addDay();
        $this->assertEquals(ConsumptionRequestBody::REFUND_PREFERENCE__UNDECLARED, $method->invoke($this->consumptionService, $transaction));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
