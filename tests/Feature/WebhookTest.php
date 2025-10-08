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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SendConsumptionInformationJob;
use App\Jobs\FinishNotificationJob;
use Readdle\AppStoreServerAPI\AppMetadata;
use Readdle\AppStoreServerAPI\ResponseBodyV2;
use Readdle\AppStoreServerAPI\TransactionInfo;
use Tests\Support\AppleSignedPayload;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Cache::flush(); // Clear cache to prevent duplicate notification detection in tests
    }

    protected function makeApp(string $bundleId = 'com.demo.app'): App
    {
        return App::query()->create([
            'name' => 'demo',
            'bundle_id' => $bundleId,
            'status' => AppStatusEnum::UN_VERIFIED->value,
        ]);
    }

    protected function meta(array $overrides = []): array
    {
        return array_merge([
            'bundleId' => 'com.demo.app',
            'bundleVersion' => '1.0.0',
            'environment' => 'LocalSandbox',
            'transactionInfo' => [
                'originalTransactionId' => '100000000000001',
                'transactionId' => '200000000000001',
                'purchaseDate' => 1700000000000,
                'price' => 199,
                'currency' => 'USD',
                'appAccountToken' => 'acct-1',
                'productId' => 'pro.monthly',
                'type' => 'Auto-Renewable Subscription',
                'originalPurchaseDate' => 1690000000000,
                'expiresDate' => 1710000000000,
                'inAppOwnershipType' => 'PURCHASED',
                'quantity' => 1,
                'revocationDate' => 1711000000000,
                'revocationReason' => 0,
            ],
            'consumptionRequestReason' => 'UNINTENDED_PURCHASE',
        ], $overrides);
    }

    protected function fakePayload(string $event, array $meta): ResponseBodyV2
    {
        $payload = AppleSignedPayload::buildResponseBodyV2FromArray($event, $meta);
        return $payload;
    }

    protected function stubDecode(ResponseBodyV2 $payload): void
    {
        $this->app->forgetInstance(IapService::class);
        $this->mock(IapService::class, function ($mock) use ($payload) {
            $mock->shouldReceive('decodePayload')->andReturn($payload);
        });

        $this->mock(AmountPriceService::class, function ($mock) {
            $mock->shouldReceive('toDollar')->andReturn(1.99);
            $mock->shouldReceive('toDollarFloat')->andReturn(1.99);
        });
    }

    public function test_test_event_updates_app_and_raw_log_created(): void
    {
        $app = $this->makeApp();

        $payload = $this->fakePayload('TEST', $this->meta());
        $this->stubDecode($payload);

        $resp = $this->postJson('/api/v1/apps/' . $app->id . '/webhook', []);
        $resp->assertOk();

        $this->assertDatabaseHas('notification_logs', [
            'app_id' => $app->id,
            'bundle_id' => 'com.demo.app',
            'notification_type' => 'TEST',
        ]);

        $app->refresh();
        $this->assertTrue($app->status === AppStatusEnum::NORMAL);

        Queue::assertPushed(FinishNotificationJob::class);
    }

    public function test_refund_event_writes_refund_log_and_increments_stats(): void
    {
        $app = $this->makeApp();

        $payload = $this->fakePayload('REFUND', $this->meta());
        $this->stubDecode($payload);

        $this->postJson('/api/v1/apps/' . $app->id . '/webhook', [])->assertOk();

        $this->assertDatabaseHas('refund_logs', [
            'app_id' => $app->id,
            'original_transaction_id' => '100000000000001',
            'transaction_id' => '200000000000001',
            'currency' => 'USD',
        ]);

        $app->refresh();
        $this->assertSame(1, (int) $app->refund_count);
        $this->assertTrue((float) $app->refund_dollars > 0);
    }

    public function test_subscribed_event_writes_transaction_log(): void
    {
        $app = $this->makeApp();
        $payload = $this->fakePayload('SUBSCRIBED', $this->meta());
        $this->stubDecode($payload);
        $this->postJson('/api/v1/apps/' . $app->id . '/webhook', [])->assertOk();

        $this->assertDatabaseHas('transaction_logs', [
            'app_id' => $app->id,
            'original_transaction_id' => '100000000000001',
            'transaction_id' => '200000000000001',
        ]);

        $app->refresh();
        $this->assertTrue((int) $app->transaction_count >= 1);
        $this->assertTrue((float) $app->transaction_dollars > 0);

        Queue::assertPushed(FinishNotificationJob::class);
    }

    public function test_did_renew_event_writes_transaction_log(): void
    {
        $app = $this->makeApp();
        $payload = $this->fakePayload('DID_RENEW', $this->meta());
        $this->stubDecode($payload);
        $this->postJson('/api/v1/apps/' . $app->id . '/webhook', [])->assertOk();

        $this->assertDatabaseHas('transaction_logs', [
            'app_id' => $app->id,
            'original_transaction_id' => '100000000000001',
            'transaction_id' => '200000000000001',
        ]);

        $app->refresh();
        $this->assertTrue((int) $app->transaction_count >= 1);
        $this->assertTrue((float) $app->transaction_dollars > 0);

        Queue::assertPushed(FinishNotificationJob::class);
    }

    public function test_offer_redeemed_event_writes_transaction_log(): void
    {
        $app = $this->makeApp();
        $payload = $this->fakePayload('OFFER_REDEEMED', $this->meta());
        $this->stubDecode($payload);
        $this->postJson('/api/v1/apps/' . $app->id . '/webhook', [])->assertOk();

        $this->assertDatabaseHas('transaction_logs', [
            'app_id' => $app->id,
            'original_transaction_id' => '100000000000001',
            'transaction_id' => '200000000000001',
        ]);

        $app->refresh();
        $this->assertTrue((int) $app->transaction_count >= 1);
        $this->assertTrue((float) $app->transaction_dollars > 0);

        Queue::assertPushed(FinishNotificationJob::class);
    }

    public function test_one_time_charge_event_writes_transaction_log(): void
    {
        $app = $this->makeApp();
        $payload = $this->fakePayload('ONE_TIME_CHARGE', $this->meta());
        $this->stubDecode($payload);
        $this->postJson('/api/v1/apps/' . $app->id . '/webhook', [])->assertOk();

        $this->assertDatabaseHas('transaction_logs', [
            'app_id' => $app->id,
            'original_transaction_id' => '100000000000001',
            'transaction_id' => '200000000000001',
        ]);

        $app->refresh();
        $this->assertTrue((int) $app->transaction_count >= 1);
        $this->assertTrue((float) $app->transaction_dollars > 0);

        Queue::assertPushed(FinishNotificationJob::class);
    }

    public function test_consumption_request_creates_log_and_increments_and_dispatches_job(): void
    {
        $app = $this->makeApp();

        $payload = $this->fakePayload('CONSUMPTION_REQUEST', $this->meta());
        $this->stubDecode($payload);

        $this->postJson('/api/v1/apps/' . $app->id . '/webhook', [])->assertOk();

        $this->assertDatabaseHas('consumption_logs', [
            'app_id' => $app->id,
            'original_transaction_id' => '100000000000001',
            'transaction_id' => '200000000000001',
        ]);

        $app->refresh();
        $this->assertSame(1, (int) $app->consumption_count);
        $this->assertTrue((float) $app->consumption_dollars > 0);

        Queue::assertPushed(SendConsumptionInformationJob::class);
        Queue::assertPushed(FinishNotificationJob::class);
    }

    public function test_bundle_id_mismatch_should_throw_and_no_logs_written(): void
    {
        $app = $this->makeApp('com.real.app');

        $payload = $this->fakePayload('TEST', $this->meta(['bundleId' => 'com.fake.app']));
        $this->stubDecode($payload);

        $resp = $this->postJson('/api/v1/apps/' . $app->id . '/webhook', []);
        $resp->assertStatus(500);

        $this->assertDatabaseMissing('notification_raw_logs', [
            'app_id' => $app->id,
            'bundle_id' => 'com.fake.app',
        ]);
    }

    public function test_missing_transaction_info_on_refund_should_fail_and_no_logs_written(): void
    {
        $app = $this->makeApp();

        $meta = $this->meta();
        $meta['transactionInfo'] = null;
        $payload = $this->fakePayload('REFUND', $meta);
        $this->stubDecode($payload);

        $resp = $this->postJson('/api/v1/apps/' . $app->id . '/webhook', []);
        $resp->assertStatus(500);

        $this->assertDatabaseMissing('refund_logs', [
            'app_id' => $app->id,
        ]);
    }

    public function test_unknown_event_only_records_raw_log_and_logs_info(): void
    {
        $app = $this->makeApp();

        Log::spy();

        $payload = $this->fakePayload('UNKNOWN_EVENT', $this->meta());
        $this->stubDecode($payload);

        $resp = $this->postJson('/api/v1/apps/' . $app->id . '/webhook', []);
        $resp->assertOk();

        $this->assertDatabaseHas('notification_logs', [
            'app_id' => $app->id,
            'notification_type' => 'UNKNOWN_EVENT',
        ]);
        $this->assertDatabaseMissing('transaction_logs', [ 'app_id' => $app->id ]);
        $this->assertDatabaseMissing('refund_logs', [ 'app_id' => $app->id ]);
        $this->assertDatabaseMissing('consumption_logs', [ 'app_id' => $app->id ]);

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message) {
                return is_string($message) && str_contains($message, 'UNKNOWN_EVENT');
            })
            ->atLeast()->once();

        Queue::assertPushed(FinishNotificationJob::class);
    }

    public function test_transaction_creates_apple_user_with_register_at(): void
    {
        $app = $this->makeApp();

        $originalPurchaseDate = 1690000000000; // milliseconds
        $meta = $this->meta([
            'transactionInfo' => array_merge($this->meta()['transactionInfo'], [
                'appAccountToken' => 'user-token-123',
                'originalPurchaseDate' => $originalPurchaseDate,
            ])
        ]);

        $payload = $this->fakePayload('SUBSCRIBED', $meta);
        $this->stubDecode($payload);

        $this->postJson('/api/v1/apps/' . $app->id . '/webhook', [])->assertOk();

        $this->assertDatabaseHas('apple_users', [
            'app_account_token' => 'user-token-123',
            'app_id' => $app->id,
            'purchased_dollars' => 1.99,
            'refunded_dollars' => 0,
        ]);

        $user = AppleUser::where('app_account_token', 'user-token-123')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->register_at);
        $this->assertInstanceOf(Carbon::class, $user->register_at);

        // Check register_at is set to originalPurchaseDate
        /** @var Carbon $registerAt */
        $registerAt = $user->register_at;
        $expectedTime = Carbon::createFromTimestamp($originalPurchaseDate / 1000);
        $this->assertEquals(
            $expectedTime->format('Y-m-d H:i:s'),
            $registerAt->format('Y-m-d H:i:s')
        );
    }

    public function test_transaction_with_existing_user_increments_purchased_dollars(): void
    {
        $app = $this->makeApp();

        // Create existing user
        $existingUser = AppleUser::create([
            'app_account_token' => 'existing-user',
            'app_id' => $app->id,
            'purchased_dollars' => 10.0,
            'refunded_dollars' => 0,
            'play_seconds' => 0,
            'register_at' => Carbon::parse('2023-01-01 00:00:00'),
        ]);

        $meta = $this->meta([
            'transactionInfo' => array_merge($this->meta()['transactionInfo'], [
                'appAccountToken' => 'existing-user',
            ])
        ]);

        $payload = $this->fakePayload('SUBSCRIBED', $meta);
        $this->stubDecode($payload);

        $this->postJson('/api/v1/apps/' . $app->id . '/webhook', [])->assertOk();

        $existingUser->refresh();
        $this->assertEquals(11.99, $existingUser->purchased_dollars);
        // register_at should not change
        $this->assertInstanceOf(Carbon::class, $existingUser->register_at);
        /** @var Carbon $registerAt */
        $registerAt = $existingUser->register_at;
        $this->assertEquals('2023-01-01 00:00:00', $registerAt->format('Y-m-d H:i:s'));
    }

    public function test_transaction_without_app_account_token_still_works(): void
    {
        $app = $this->makeApp();

        $meta = $this->meta([
            'transactionInfo' => array_merge($this->meta()['transactionInfo'], [
                'appAccountToken' => null,
            ])
        ]);

        $payload = $this->fakePayload('SUBSCRIBED', $meta);
        $this->stubDecode($payload);

        $resp = $this->postJson('/api/v1/apps/' . $app->id . '/webhook', []);
        $resp->assertOk();

        // Transaction log should be created
        $this->assertDatabaseHas('transaction_logs', [
            'app_id' => $app->id,
        ]);

        // But no AppleUser created
        $this->assertDatabaseMissing('apple_users', [
            'app_id' => $app->id,
        ]);
    }

    public function test_refund_increments_user_refunded_dollars(): void
    {
        $app = $this->makeApp();

        // Create user
        $user = AppleUser::create([
            'app_account_token' => 'refund-user',
            'app_id' => $app->id,
            'purchased_dollars' => 50.0,
            'refunded_dollars' => 0,
            'play_seconds' => 0,
            'register_at' => Carbon::now(),
        ]);

        $meta = $this->meta([
            'transactionInfo' => array_merge($this->meta()['transactionInfo'], [
                'appAccountToken' => 'refund-user',
            ])
        ]);

        $payload = $this->fakePayload('REFUND', $meta);
        $this->stubDecode($payload);

        $this->postJson('/api/v1/apps/' . $app->id . '/webhook', [])->assertOk();

        $user->refresh();
        $this->assertEquals(1.99, $user->refunded_dollars);
        $this->assertEquals(50.0, $user->purchased_dollars); // should not change
    }

    public function test_refund_without_existing_user_does_not_create_user(): void
    {
        $app = $this->makeApp();

        $meta = $this->meta([
            'transactionInfo' => array_merge($this->meta()['transactionInfo'], [
                'appAccountToken' => 'non-existent-user',
            ])
        ]);

        $payload = $this->fakePayload('REFUND', $meta);
        $this->stubDecode($payload);

        $resp = $this->postJson('/api/v1/apps/' . $app->id . '/webhook', []);
        $resp->assertOk();

        // Refund log should be created
        $this->assertDatabaseHas('refund_logs', [
            'app_id' => $app->id,
        ]);

        // But no AppleUser created
        $this->assertDatabaseMissing('apple_users', [
            'app_account_token' => 'non-existent-user',
        ]);
    }

    public function test_multiple_transactions_accumulate_purchased_dollars(): void
    {
        $app = $this->makeApp();

        $meta = $this->meta([
            'transactionInfo' => array_merge($this->meta()['transactionInfo'], [
                'appAccountToken' => 'multi-user',
            ])
        ]);

        // Create three different payloads with unique UUIDs
        $payload1 = $this->fakePayload('SUBSCRIBED', $meta);
        $payload2 = $this->fakePayload('DID_RENEW', $meta);
        $payload3 = $this->fakePayload('ONE_TIME_CHARGE', $meta);

        // Mock IapService to return different payloads for each call
        $this->app->forgetInstance(IapService::class);
        $this->mock(IapService::class, function ($mock) use ($payload1, $payload2, $payload3) {
            $mock->shouldReceive('decodePayload')
                ->times(3)
                ->andReturn($payload1, $payload2, $payload3);
        });

        // Mock AmountPriceService for all calls
        $this->mock(AmountPriceService::class, function ($mock) {
            $mock->shouldReceive('toDollar')->andReturn(1.99);
            $mock->shouldReceive('toDollarFloat')->andReturn(1.99);
        });

        // First transaction
        $this->postJson('/api/v1/apps/' . $app->id . '/webhook', [])->assertOk();

        // Second transaction
        $this->postJson('/api/v1/apps/' . $app->id . '/webhook', [])->assertOk();

        // Third transaction
        $this->postJson('/api/v1/apps/' . $app->id . '/webhook', [])->assertOk();

        $user = AppleUser::where('app_account_token', 'multi-user')->first();
        $this->assertNotNull($user);
        $this->assertEquals(5.97, $user->purchased_dollars); // 1.99 * 3
        $this->assertEquals(0, $user->refunded_dollars);
    }

    public function test_transaction_with_zero_timestamp_uses_current_time(): void
    {
        Carbon::setTestNow('2024-06-15 10:30:00');

        $app = $this->makeApp();

        $meta = $this->meta([
            'transactionInfo' => array_merge($this->meta()['transactionInfo'], [
                'appAccountToken' => 'zero-time-user',
                'originalPurchaseDate' => 0,
            ])
        ]);

        $payload = $this->fakePayload('SUBSCRIBED', $meta);
        $this->stubDecode($payload);

        $this->postJson('/api/v1/apps/' . $app->id . '/webhook', [])->assertOk();

        $user = AppleUser::where('app_account_token', 'zero-time-user')->first();
        $this->assertNotNull($user);
        $this->assertInstanceOf(Carbon::class, $user->register_at);
        /** @var Carbon $registerAt */
        $registerAt = $user->register_at;
        $this->assertEquals(
            Carbon::now()->format('Y-m-d H:i:s'),
            $registerAt->format('Y-m-d H:i:s')
        );

        Carbon::setTestNow();
    }
}


