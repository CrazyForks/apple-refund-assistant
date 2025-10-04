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
        $rawLog = $this->createMock(\App\Models\NotificationLog::class);
        $rawLog->method('getTransactionInfo')->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('invalid transaction info');

        $this->dao->storeLog($app, $rawLog);
    }
}

