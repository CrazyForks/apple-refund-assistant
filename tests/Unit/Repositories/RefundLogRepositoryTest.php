<?php

namespace Tests\Unit\Repositories;

use App\Exceptions\InvalidTransactionInfoException;
use App\Models\App;
use App\Models\NotificationLog;
use App\Repositories\RefundLogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundLogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected RefundLogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new RefundLogRepository();
    }

    public function test_store_log_throws_exception_when_transaction_info_is_null(): void
    {
        $app = App::factory()->create();
        
        // Create a notification log with no transaction info in payload
        $log = NotificationLog::factory()->create([
            'app_id' => $app->id,
            'payload' => json_encode([
                'notificationType' => 'REFUND',
                'notificationUUID' => 'test-uuid',
                'data' => [
                    'bundleId' => 'com.test.app',
                    'environment' => 'Sandbox',
                ],
            ]),
        ]);

        $this->expectException(InvalidTransactionInfoException::class);
        $this->expectExceptionMessage('invalid transaction info');

        $this->repository->storeLog($app, $log);
    }
}

