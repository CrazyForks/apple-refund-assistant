<?php

namespace Tests\Unit\Dao;

use App\Dao\TransactionLogDao;
use App\Models\App;
use App\Models\ConsumptionLog;
use App\Models\TransactionLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionLogDaoTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionLogDao $dao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = new TransactionLogDao();
    }

    public function test_find_transaction_by_consumption_returns_latest_transaction(): void
    {
        $app = App::factory()->create();

        // Create multiple transactions with same original_transaction_id
        $transaction1 = TransactionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => 'orig-123',
            'created_at' => now()->subHours(2),
        ]);

        $transaction2 = TransactionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => 'orig-123',
            'created_at' => now()->subHour(),
        ]);

        $consumptionLog = ConsumptionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => 'orig-123',
        ]);

        $result = $this->dao->findTransactionByConsumption($consumptionLog);

        // Should return the latest transaction (highest ID)
        $this->assertNotNull($result);
        $this->assertEquals($transaction2->id, $result->id);
    }

    public function test_find_transaction_by_consumption_returns_null_when_not_found(): void
    {
        $app = App::factory()->create();

        $consumptionLog = ConsumptionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => 'non-existent-id',
        ]);

        $result = $this->dao->findTransactionByConsumption($consumptionLog);

        $this->assertNull($result);
    }

    public function test_find_transaction_by_consumption_filters_by_app_id(): void
    {
        $app1 = App::factory()->create();
        $app2 = App::factory()->create();

        // Create transaction for app1
        TransactionLog::factory()->create([
            'app_id' => $app1->id,
            'original_transaction_id' => 'orig-123',
        ]);

        // Create consumption log for app2
        $consumptionLog = ConsumptionLog::factory()->create([
            'app_id' => $app2->id,
            'original_transaction_id' => 'orig-123',
        ]);

        $result = $this->dao->findTransactionByConsumption($consumptionLog);

        // Should not find transaction from different app
        $this->assertNull($result);
    }

    public function test_find_transaction_by_consumption_orders_by_id_desc(): void
    {
        $app = App::factory()->create();

        // Create 3 transactions with same original_transaction_id
        $t1 = TransactionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => 'orig-123',
        ]);

        $t2 = TransactionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => 'orig-123',
        ]);

        $t3 = TransactionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => 'orig-123',
        ]);

        $consumptionLog = ConsumptionLog::factory()->create([
            'app_id' => $app->id,
            'original_transaction_id' => 'orig-123',
        ]);

        $result = $this->dao->findTransactionByConsumption($consumptionLog);

        // Should return the transaction with highest ID (most recent)
        $this->assertEquals($t3->id, $result->id);
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
}

