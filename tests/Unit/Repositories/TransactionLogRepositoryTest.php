<?php

namespace Tests\Unit\Repositories;

use App\Exceptions\InvalidTransactionInfoException;
use App\Models\App;
use App\Models\ConsumptionLog;
use App\Models\NotificationLog;
use App\Models\TransactionLog;
use App\Repositories\TransactionLogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionLogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionLogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TransactionLogRepository();
    }

    public function test_store_log_throws_exception_when_transaction_info_is_null(): void
    {
        $app = App::factory()->create();
        
        // Create a notification log with no transaction info in payload
        $log = NotificationLog::factory()->create([
            'app_id' => $app->id,
            'payload' => json_encode([
                'notificationType' => 'SUBSCRIBED',
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

    public function test_find_transaction_by_consumption(): void
    {
        $app = App::factory()->create();
        $log = NotificationLog::factory()->create(['app_id' => $app->id]);
        
        $transactionLog = TransactionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => '123456',
        ]);

        $consumptionLog = ConsumptionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => '123456',
        ]);

        $result = $this->repository->findTransactionByConsumption($consumptionLog);

        $this->assertNotNull($result);
        $this->assertEquals($transactionLog->id, $result->id);
    }

    public function test_find_transaction_by_consumption_returns_latest(): void
    {
        $app = App::factory()->create();
        
        // Create multiple transactions with same original_transaction_id
        $oldTransaction = TransactionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => '123456',
            'created_at' => now()->subDays(2),
        ]);

        $newerTransaction = TransactionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => '123456',
            'created_at' => now()->subDay(),
        ]);

        $consumptionLog = ConsumptionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => '123456',
        ]);

        $result = $this->repository->findTransactionByConsumption($consumptionLog);

        $this->assertNotNull($result);
        // Should return the newer transaction (higher ID)
        $this->assertEquals($newerTransaction->id, $result->id);
    }
}

