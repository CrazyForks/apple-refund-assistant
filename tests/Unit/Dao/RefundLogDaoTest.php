<?php

namespace Tests\Unit\Dao;

use App\Dao\RefundLogDao;
use App\Models\App;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundLogDaoTest extends TestCase
{
    use RefreshDatabase;

    protected RefundLogDao $dao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = new RefundLogDao();
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

    public function test_store_log_creates_refund_log_successfully(): void
    {
        $app = App::factory()->create();

        // Create payload with correct nested structure
        $payload = json_encode([
            'notificationType' => 'REFUND',
            'notificationUUID' => 'uuid-refund-test-456',
            'data' => [
                'bundleId' => 'com.test.refund',
                'environment' => 'Production',
                'signedTransactionInfo' => [
                    'decoded' => [
                        'originalTransactionId' => 'orig-refund-123',
                        'transactionId' => 'trans-refund-456',
                        'appAccountToken' => 'token-refund-789',
                        'currency' => 'USD',
                        'purchaseDate' => 1700000000000,  // milliseconds
                        'price' => 199,  // cents
                        'revocationDate' => 1710000000000,  // milliseconds
                        'revocationReason' => 0,
                    ],
                ],
            ],
        ]);

        // Create notification log with factory
        $rawLog = \App\Models\NotificationLog::factory()->create([
            'app_id' => $app->id,
            'bundle_id' => 'com.test.refund',
            'bundle_version' => '2.0.0',
            'environment' => 'Production',
            'notification_uuid' => 'uuid-refund-test-456',
            'notification_type' => 'REFUND',
            'payload' => $payload,
        ]);

        $result = $this->dao->storeLog($app, $rawLog);

        $this->assertInstanceOf(\App\Models\RefundLog::class, $result);
        $this->assertEquals($rawLog->id, $result->id);
        $this->assertEquals($app->id, $result->app_id);
        $this->assertEquals('com.test.refund', $result->bundle_id);
        $this->assertEquals('orig-refund-123', $result->original_transaction_id);
        $this->assertEquals('trans-refund-456', $result->transaction_id);
        $this->assertEquals('token-refund-789', $result->app_account_token);
        $this->assertEquals('USD', $result->currency);
        $this->assertEquals(1.99, $result->price);
        $this->assertEquals(1700000000, $result->purchase_date);
        $this->assertEquals(1710000000, $result->refund_date);
        $this->assertEquals('code[0]', $result->refund_reason);
    }
}

