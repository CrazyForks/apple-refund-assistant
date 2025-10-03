<?php

namespace Tests\Feature;

use App\Enums\AppStatusEnum;
use App\Models\App;
use App\Models\ConsumptionLog;
use App\Models\NotificationRawLog;
use App\Models\RefundLog;
use App\Models\TransactionLog;
use App\Services\AmountPriceService;
use App\Services\IapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendConsumptionInformationJob;
use App\Jobs\SendRequestToAppNotificationUrlJob;
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
        });
    }

    public function test_test_event_updates_app_and_raw_log_created(): void
    {
        $app = $this->makeApp();

        $payload = $this->fakePayload('TEST', $this->meta());
        $this->stubDecode($payload);

        $resp = $this->postJson('/api/v1/apps/' . $app->id . '/webhook', []);
        $resp->assertOk();

        $this->assertDatabaseHas('notification_raw_logs', [
            'app_id' => $app->id,
            'bundle_id' => 'com.demo.app',
            'notification_type' => 'TEST',
        ]);

        $app->refresh();
        $this->assertTrue($app->status === AppStatusEnum::NORMAL);

        Queue::assertPushed(SendRequestToAppNotificationUrlJob::class);
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

    public function test_transaction_events_write_transaction_log_and_increment_stats(): void
    {
        $app = $this->makeApp();

        foreach (['SUBSCRIBED', 'DID_RENEW', 'OFFER_REDEEMED', 'ONE_TIME_CHARGE'] as $event) {
            $payload = $this->fakePayload($event, $this->meta());
            $this->stubDecode($payload);
            $this->postJson('/api/v1/apps/' . $app->id . '/webhook', [])->assertOk();
        }

        $this->assertDatabaseHas('transaction_logs', [
            'app_id' => $app->id,
            'original_transaction_id' => '100000000000001',
            'transaction_id' => '200000000000001',
        ]);

        $app->refresh();
        $this->assertTrue((int) $app->transaction_count >= 1);
        $this->assertTrue((float) $app->transaction_dollars > 0);

        Queue::assertPushed(SendRequestToAppNotificationUrlJob::class);
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
        Queue::assertPushed(SendRequestToAppNotificationUrlJob::class);
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

        $this->assertDatabaseHas('notification_raw_logs', [
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

        Queue::assertPushed(SendRequestToAppNotificationUrlJob::class);
    }
}


