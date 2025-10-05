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
 * Webhook Integration Test
 *
 * Tests the complete webhook flow: from app creation to handling various notification types
 *
 * Note: These tests simulate the complete Apple Server Notification flow,
 * including TEST notifications, transaction notifications, consumption requests, and refund notifications.
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
     * Complete integration test: from app creation to refund
     *
     * Test scenarios:
     * 1. Create app (UN_VERIFIED status)
     * 2. Receive TEST notification, app status becomes NORMAL
     * 3. Receive SUBSCRIBED notification, create transaction and user
     * 4. Receive DID_RENEW notification, accumulate transaction amount
     * 5. Receive ONE_TIME_CHARGE notification, add one-time purchase
     * 6. Receive CONSUMPTION_REQUEST notification, handle consumption request
     * 7. Receive REFUND notification, handle refund
     */
    public function test_complete_webhook_flow_from_app_creation_to_refund(): void
    {
        // Create app
        $app = App::factory()->create([
            'bundle_id' => 'com.integration.test',
            'status' => AppStatusEnum::UN_VERIFIED->value,
        ]);

        // Mock all service calls at once
        $this->mockAllServices($app, [
            ['event' => 'TEST', 'userToken' => 'user-001', 'price' => 4.99],
            ['event' => 'SUBSCRIBED', 'userToken' => 'user-001', 'price' => 4.99],
            ['event' => 'DID_RENEW', 'userToken' => 'user-001', 'price' => 4.99],
            ['event' => 'ONE_TIME_CHARGE', 'userToken' => 'user-001', 'price' => 9.99],
            ['event' => 'CONSUMPTION_REQUEST', 'userToken' => 'user-001', 'price' => 4.99],
            ['event' => 'REFUND', 'userToken' => 'user-001', 'price' => 4.99],
        ]);

        // TEST notification
        $this->sendWebhookNotification($app);
        $app->refresh();
        $this->assertEquals(AppStatusEnum::NORMAL, $app->status);

        // First subscription
        $this->sendWebhookNotification($app);
        $app->refresh();
        $this->assertEquals(1, $app->transaction_count);

        $user = AppleUser::where('app_account_token', 'user-001')->first();
        $this->assertNotNull($user);
        $this->assertEquals(4.99, (float) $user->purchased_dollars);

        // Subscription renewal
        $this->sendWebhookNotification($app);
        $app->refresh();
        $this->assertEquals(2, $app->transaction_count);

        $user->refresh();
        $this->assertEquals(9.98, (float) $user->purchased_dollars);

        // One-time purchase
        $this->sendWebhookNotification($app);
        $app->refresh();
        $this->assertEquals(3, $app->transaction_count);

        $user->refresh();
        $this->assertEquals(19.97, (float) $user->purchased_dollars);

        // Consumption request
        $this->sendWebhookNotification($app);
        $app->refresh();
        $this->assertEquals(1, $app->consumption_count);
        Queue::assertPushed(SendConsumptionInformationJob::class);

        // Refund
        $this->sendWebhookNotification($app);
        $app->refresh();
        $this->assertEquals(1, $app->refund_count);
        $this->assertEquals(4.99, (float) $app->refund_dollars);

        $user->refresh();
        $this->assertEquals(4.99, (float) $user->refunded_dollars);

        // Verify all records
        $this->assertEquals(6, NotificationLog::where('app_id', $app->id)->count());
        $this->assertEquals(3, TransactionLog::where('app_id', $app->id)->count());
        $this->assertEquals(1, RefundLog::where('app_id', $app->id)->count());
        $this->assertEquals(1, ConsumptionLog::where('app_id', $app->id)->count());
    }

    /**
     * Test multiple users scenario
     */
    public function test_multiple_users_webhook_flow(): void
    {
        $app = App::factory()->create([
            'bundle_id' => 'com.multi.test',
            'status' => AppStatusEnum::UN_VERIFIED->value,
        ]);

        // Mock all service calls at once
        $this->mockAllServices($app, [
            ['event' => 'TEST', 'userToken' => 'user-a', 'price' => 4.99],
            ['event' => 'SUBSCRIBED', 'userToken' => 'user-a', 'price' => 9.99],
            ['event' => 'SUBSCRIBED', 'userToken' => 'user-b', 'price' => 14.99],
            ['event' => 'REFUND', 'userToken' => 'user-a', 'price' => 9.99],
        ]);

        $this->sendWebhookNotification($app);

        // User A's transaction
        $this->sendWebhookNotification($app);

        // User B's transaction
        $this->sendWebhookNotification($app);

        $userA = AppleUser::where('app_account_token', 'user-a')->first();
        $userB = AppleUser::where('app_account_token', 'user-b')->first();

        $this->assertNotNull($userA);
        $this->assertNotNull($userB);
        $this->assertEquals(9.99, (float) $userA->purchased_dollars);
        $this->assertEquals(14.99, (float) $userB->purchased_dollars);

        // User A's refund
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
     * Test transactions without user token (anonymous users)
     */
    public function test_webhook_flow_without_user_token(): void
    {
        $app = App::factory()->create([
            'bundle_id' => 'com.anon.test',
            'status' => AppStatusEnum::UN_VERIFIED->value,
        ]);

        // Mock all service calls at once
        $this->mockAllServices($app, [
            ['event' => 'TEST', 'userToken' => null, 'price' => 4.99],
            ['event' => 'ONE_TIME_CHARGE', 'userToken' => null, 'price' => 4.99],
            ['event' => 'REFUND', 'userToken' => null, 'price' => 4.99],
        ]);

        $this->sendWebhookNotification($app);

        // Transaction without user token
        $this->sendWebhookNotification($app);

        $app->refresh();
        $this->assertEquals(1, $app->transaction_count);
        $this->assertEquals(0, AppleUser::where('app_id', $app->id)->count());

        // Refund without user token
        $this->sendWebhookNotification($app);

        $app->refresh();
        $this->assertEquals(1, $app->refund_count);
        $this->assertEquals(0, AppleUser::where('app_id', $app->id)->count());
    }

    /**
     * Test bundle_id mismatch scenario
     */
    public function test_webhook_with_bundle_id_mismatch_throws_exception(): void
    {
        $app = App::factory()->create([
            'bundle_id' => 'com.real.bundle',
            'status' => AppStatusEnum::UN_VERIFIED->value,
        ]);

        // Mock a mismatched bundle_id
        $payload = $this->buildPayload('TEST', 'com.wrong.bundle', null, 4.99);

        $this->mock(IapService::class, function (Mockery\MockInterface $mock) use ($payload) {
            $mock->shouldReceive('decodePayload')->once()->andReturn($payload);
        });

        $response = $this->postJson('/api/v1/apps/' . $app->id . '/webhook', []);
        $response->assertStatus(500);

        $this->assertEquals(1, NotificationLog::where('app_id', $app->id)->count());
    }

    // =============== Helper Methods ===============

    /**
     * Send webhook notification and verify response
     */
    protected function sendWebhookNotification(App $app): void
    {
        $response = $this->postJson('/api/v1/apps/' . $app->id . '/webhook', []);
        $response->assertOk();
    }

    /**
     * Build ResponseBodyV2 payload for testing
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
                'price' => (int) ($price * 100), // Convert to cents
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
     * Mock all service calls at once
     *
     * @param App $app
     * @param array $notifications Array of notifications, each element contains ['event' => string, 'userToken' => ?string, 'price' => float]
     */
    protected function mockAllServices(App $app, array $notifications): void
    {
        // Build all payloads
        $payloads = [];
        foreach ($notifications as $notification) {
            $payloads[] = $this->buildPayload(
                $notification['event'],
                $app->bundle_id,
                $notification['userToken'],
                $notification['price']
            );
        }

        // Mock IapService - call decodePayload once per notification
        $this->mock(IapService::class, function (Mockery\MockInterface $mock) use ($payloads) {
            foreach ($payloads as $payload) {
                $mock->shouldReceive('decodePayload')->once()->andReturn($payload);
            }
        });

        // Mock AmountPriceService - all notifications except TEST need to call toDollarFloat
        $this->mock(AmountPriceService::class, function (Mockery\MockInterface $mock) use ($notifications) {
            foreach ($notifications as $notification) {
                // TEST notification doesn't need price conversion
                if ($notification['event'] !== 'TEST') {
                    $mock->shouldReceive('toDollarFloat')
                        ->once()
                        ->andReturn($notification['price']);
                }
            }
        });
    }
}
