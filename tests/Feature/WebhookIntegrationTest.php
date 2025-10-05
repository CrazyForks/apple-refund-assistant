<?php

namespace Tests\Feature;

use App\Enums\AppStatusEnum;
use App\Models\App;
use App\Models\AppleUser;
use App\Models\ConsumptionLog;
use App\Models\NotificationLog;
use App\Models\RefundLog;
use App\Models\TransactionLog;
use App\Services\AmountPriceService;
use App\Services\IapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendConsumptionInformationJob;
use App\Jobs\FinishNotificationJob;
use Mockery;
use Readdle\AppStoreServerAPI\ResponseBodyV2;
use Tests\Support\AppleSignedPayload;
use Tests\TestCase;

/**
 * Webhook 集成测试
 *
 * 测试完整的webhook流程：从创建应用到各种通知类型的处理
 *
 * 注意：这些测试模拟了完整的Apple Server Notification流程，
 * 包括TEST通知、交易通知、消费请求和退款通知。
 */
class WebhookIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    /**
     * 完整流程集成测试：从创建应用到退款
     *
     * 测试场景：
     * 1. 创建应用（UN_VERIFIED状态）
     * 2. 接收TEST通知，应用状态变为NORMAL
     * 3. 接收SUBSCRIBED通知，创建交易和用户
     * 4. 接收DID_RENEW通知，累计交易金额
     * 5. 接收ONE_TIME_CHARGE通知，增加一次性购买
     * 6. 接收CONSUMPTION_REQUEST通知，处理消费请求
     * 7. 接收REFUND通知，处理退款
     */
    public function test_complete_webhook_flow_from_app_creation_to_refund(): void
    {
        // 创建应用
        $app = App::factory()->create([
            'bundle_id' => 'com.integration.test',
            'status' => AppStatusEnum::UN_VERIFIED->value,
        ]);

        // 一次性 mock 所有服务调用
        $this->mockAllServices($app, [
            ['event' => 'TEST', 'userToken' => 'user-001', 'price' => 4.99],
            ['event' => 'SUBSCRIBED', 'userToken' => 'user-001', 'price' => 4.99],
            ['event' => 'DID_RENEW', 'userToken' => 'user-001', 'price' => 4.99],
            ['event' => 'ONE_TIME_CHARGE', 'userToken' => 'user-001', 'price' => 9.99],
            ['event' => 'CONSUMPTION_REQUEST', 'userToken' => 'user-001', 'price' => 4.99],
            ['event' => 'REFUND', 'userToken' => 'user-001', 'price' => 4.99],
        ]);

        // TEST通知
        $this->sendWebhookNotification($app);
        $app->refresh();
        $this->assertEquals(AppStatusEnum::NORMAL, $app->status);

        // 首次订阅
        $this->sendWebhookNotification($app);
        $app->refresh();
        $this->assertEquals(1, $app->transaction_count);

        $user = AppleUser::where('app_account_token', 'user-001')->first();
        $this->assertNotNull($user);
        $this->assertEquals(4.99, (float) $user->purchased_dollars);

        // 订阅续订
        $this->sendWebhookNotification($app);
        $app->refresh();
        $this->assertEquals(2, $app->transaction_count);

        $user->refresh();
        $this->assertEquals(9.98, (float) $user->purchased_dollars);

        // 一次性购买
        $this->sendWebhookNotification($app);
        $app->refresh();
        $this->assertEquals(3, $app->transaction_count);

        $user->refresh();
        $this->assertEquals(19.97, (float) $user->purchased_dollars);

        // 消费请求
        $this->sendWebhookNotification($app);
        $app->refresh();
        $this->assertEquals(1, $app->consumption_count);
        Queue::assertPushed(SendConsumptionInformationJob::class);

        // 退款
        $this->sendWebhookNotification($app);
        $app->refresh();
        $this->assertEquals(1, $app->refund_count);
        $this->assertEquals(4.99, (float) $app->refund_dollars);

        $user->refresh();
        $this->assertEquals(4.99, (float) $user->refunded_dollars);

        // 验证所有记录
        $this->assertEquals(6, NotificationLog::where('app_id', $app->id)->count());
        $this->assertEquals(3, TransactionLog::where('app_id', $app->id)->count());
        $this->assertEquals(1, RefundLog::where('app_id', $app->id)->count());
        $this->assertEquals(1, ConsumptionLog::where('app_id', $app->id)->count());
    }

    /**
     * 测试多用户场景
     */
    public function test_multiple_users_webhook_flow(): void
    {
        $app = App::factory()->create([
            'bundle_id' => 'com.multi.test',
            'status' => AppStatusEnum::UN_VERIFIED->value,
        ]);

        // 一次性 mock 所有服务调用
        $this->mockAllServices($app, [
            ['event' => 'TEST', 'userToken' => 'user-a', 'price' => 4.99],
            ['event' => 'SUBSCRIBED', 'userToken' => 'user-a', 'price' => 9.99],
            ['event' => 'SUBSCRIBED', 'userToken' => 'user-b', 'price' => 14.99],
            ['event' => 'REFUND', 'userToken' => 'user-a', 'price' => 9.99],
        ]);

        $this->sendWebhookNotification($app);

        // 用户A的交易
        $this->sendWebhookNotification($app);

        // 用户B的交易
        $this->sendWebhookNotification($app);

        $userA = AppleUser::where('app_account_token', 'user-a')->first();
        $userB = AppleUser::where('app_account_token', 'user-b')->first();

        $this->assertNotNull($userA);
        $this->assertNotNull($userB);
        $this->assertEquals(9.99, (float) $userA->purchased_dollars);
        $this->assertEquals(14.99, (float) $userB->purchased_dollars);

        // 用户A的退款
        $this->sendWebhookNotification($app);

        $userA->refresh();
        $userB->refresh();
        $this->assertEquals(9.99, (float) $userA->refunded_dollars);
        $this->assertEquals(0, (float) $userB->refunded_dollars);

        $app->refresh();
        $this->assertEquals(2, $app->transaction_count);
        $this->assertEquals(1, $app->refund_count);
    }

    /**
     * 测试无用户token的交易（匿名用户）
     */
    public function test_webhook_flow_without_user_token(): void
    {
        $app = App::factory()->create([
            'bundle_id' => 'com.anon.test',
            'status' => AppStatusEnum::UN_VERIFIED->value,
        ]);

        // 一次性 mock 所有服务调用
        $this->mockAllServices($app, [
            ['event' => 'TEST', 'userToken' => null, 'price' => 4.99],
            ['event' => 'ONE_TIME_CHARGE', 'userToken' => null, 'price' => 4.99],
            ['event' => 'REFUND', 'userToken' => null, 'price' => 4.99],
        ]);

        $this->sendWebhookNotification($app);

        // 无用户token的交易
        $this->sendWebhookNotification($app);

        $app->refresh();
        $this->assertEquals(1, $app->transaction_count);
        $this->assertEquals(0, AppleUser::where('app_id', $app->id)->count());

        // 无用户token的退款
        $this->sendWebhookNotification($app);

        $app->refresh();
        $this->assertEquals(1, $app->refund_count);
        $this->assertEquals(0, AppleUser::where('app_id', $app->id)->count());
    }

    /**
     * 测试bundle_id不匹配的情况
     */
    public function test_webhook_with_bundle_id_mismatch_throws_exception(): void
    {
        $app = App::factory()->create([
            'bundle_id' => 'com.real.bundle',
            'status' => AppStatusEnum::UN_VERIFIED->value,
        ]);

        // Mock 一个不匹配的 bundle_id
        $payload = $this->buildPayload('TEST', 'com.wrong.bundle', null, 4.99);

        $this->mock(IapService::class, function (Mockery\MockInterface $mock) use ($payload) {
            $mock->shouldReceive('decodePayload')->once()->andReturn($payload);
        });

        $response = $this->postJson('/api/v1/apps/' . $app->id . '/webhook', []);
        $response->assertStatus(500);

        $this->assertEquals(1, NotificationLog::where('app_id', $app->id)->count());
    }

    // =============== 辅助方法 ===============

    /**
     * 发送webhook通知并验证响应
     */
    protected function sendWebhookNotification(App $app): void
    {
        $response = $this->postJson('/api/v1/apps/' . $app->id . '/webhook', []);
        $response->assertOk();
    }

    /**
     * 构建测试用的ResponseBodyV2 payload
     */
    protected function buildPayload(string $event, string $bundleId, ?string $userToken, float $price): ResponseBodyV2
    {
        $now = (int) (microtime(true) * 1000);

        $meta = [
            'bundleId' => $bundleId,
            'bundleVersion' => '1.0.0',
            'environment' => 'Sandbox',
            'transactionInfo' => [
                'originalTransactionId' => '100000000000001',
                'transactionId' => '200000000000001',
                'purchaseDate' => $now,
                'originalPurchaseDate' => $now - 1000000000,
                'price' => (int) ($price * 100), // 转换为分
                'currency' => 'USD',
                'appAccountToken' => $userToken,
                'productId' => 'pro.monthly',
                'type' => 'Auto-Renewable Subscription',
                'inAppOwnershipType' => 'PURCHASED',
                'quantity' => 1,
            ],
        ];

        return AppleSignedPayload::buildResponseBodyV2FromArray($event, $meta);
    }

    /**
     * 一次性 mock 所有服务调用
     *
     * @param App $app
     * @param array $notifications 通知数组，每个元素包含 ['event' => string, 'userToken' => ?string, 'price' => float]
     */
    protected function mockAllServices(App $app, array $notifications): void
    {
        // 构建所有 payload
        $payloads = [];
        foreach ($notifications as $notification) {
            $payloads[] = $this->buildPayload(
                $notification['event'],
                $app->bundle_id,
                $notification['userToken'],
                $notification['price']
            );
        }

        // Mock IapService - 每个通知调用一次 decodePayload
        $this->mock(IapService::class, function (Mockery\MockInterface $mock) use ($payloads) {
            foreach ($payloads as $payload) {
                $mock->shouldReceive('decodePayload')->once()->andReturn($payload);
            }
        });

        // Mock AmountPriceService - 除了 TEST 通知，其他都需要调用 toDollarFloat
        $this->mock(AmountPriceService::class, function (Mockery\MockInterface $mock) use ($notifications) {
            foreach ($notifications as $notification) {
                // TEST 通知不需要价格转换
                if ($notification['event'] !== 'TEST') {
                    $mock->shouldReceive('toDollarFloat')
                        ->once()
                        ->andReturn($notification['price']);
                }
            }
        });
    }
}
