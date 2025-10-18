<?php

namespace Tests\Unit\Dao;

use App\Dao\ConsumptionLogDao;
use App\Models\App;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumptionLogDaoTest extends TestCase
{
    use RefreshDatabase;

    protected ConsumptionLogDao $dao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = new ConsumptionLogDao();
    }

    public function test_store_log_throws_exception_when_transaction_info_is_null(): void
    {
        $app = App::factory()->create();

        // Create a raw log without transaction info (by mocking)
        $rawLog = $this->mock(\App\Models\NotificationLog::class, function ($mock) {
            $mock->shouldReceive('getTransactionInfo')->andReturn(null);
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('invalid transaction info');

        $this->dao->storeLog($app, $rawLog);
    }

    public function test_store_log_creates_consumption_log_successfully(): void
    {
        $app = App::factory()->create();

        // Create payload with correct nested structure
        $payload = json_encode([
            'notificationType' => 'CONSUMPTION_REQUEST',
            'notificationUUID' => 'uuid-test-123',
            'data' => [
                'bundleId' => 'com.test.app',
                'environment' => 'Sandbox',
                'signedTransactionInfo' => [
                    'decoded' => [
                        'originalTransactionId' => 'orig-123',
                        'transactionId' => 'trans-456',
                        'appAccountToken' => 'token-789',
                        'purchaseDate' => 1700000000000,
                        'price' => 199,
                        'currency' => 'USD',
                    ],
                ],
                'consumptionRequestReason' => 'UNINTENDED_PURCHASE',
            ],
        ]);

        // Create notification log with factory
        $rawLog = \App\Models\NotificationLog::factory()->create([
            'app_id' => $app->id,
            'bundle_id' => 'com.test.app',
            'bundle_version' => '1.0.0',
            'environment' => 'Sandbox',
            'notification_uuid' => 'uuid-test-123',
            'notification_type' => 'CONSUMPTION_REQUEST',
            'payload' => $payload,
        ]);

        $result = $this->dao->storeLog($app, $rawLog);

        $this->assertInstanceOf(\App\Models\ConsumptionLog::class, $result);
        $this->assertEquals($rawLog->id, $result->id);
        $this->assertEquals($app->id, $result->app_id);
        $this->assertEquals('com.test.app', $result->bundle_id);
        $this->assertEquals('orig-123', $result->original_transaction_id);
        $this->assertEquals('trans-456', $result->transaction_id);
        $this->assertEquals('token-789', $result->app_account_token);
        $this->assertEquals(\App\Enums\ConsumptionLogStatusEnum::PENDING, $result->status);
    }

    public function test_update_status_without_message(): void
    {
        $app = App::factory()->create();
        $log = \App\Models\ConsumptionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => 'orig-update-test',
            'status' => \App\Enums\ConsumptionLogStatusEnum::PENDING,
        ]);

        $this->dao->updateStatus('orig-update-test', \App\Enums\ConsumptionLogStatusEnum::SUCCESS);

        $log->refresh();
        $this->assertEquals(\App\Enums\ConsumptionLogStatusEnum::SUCCESS, $log->status);
        $this->assertNull($log->status_msg);
    }

    public function test_update_status_with_message(): void
    {
        $app = App::factory()->create();
        $log = \App\Models\ConsumptionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => 'orig-msg-test',
            'status' => \App\Enums\ConsumptionLogStatusEnum::PENDING,
        ]);

        $this->dao->updateStatus('orig-msg-test', \App\Enums\ConsumptionLogStatusEnum::FAIL, 'Error occurred');

        $log->refresh();
        $this->assertEquals(\App\Enums\ConsumptionLogStatusEnum::FAIL, $log->status);
        $this->assertEquals('Error occurred', $log->status_msg);
    }
}

