<?php

namespace Tests\Unit\Repositories;

use App\Enums\ConsumptionLogStatusEnum;
use App\Exceptions\InvalidTransactionInfoException;
use App\Models\App;
use App\Models\NotificationLog;
use App\Repositories\ConsumptionLogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumptionLogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ConsumptionLogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ConsumptionLogRepository;
    }

    public function test_store_log_throws_exception_when_transaction_info_is_null(): void
    {
        $app = App::factory()->create();

        // Create a notification log with no transaction info in payload
        $log = NotificationLog::factory()->create([
            'app_id' => $app->id,
            'payload' => json_encode([
                'notificationType' => 'CONSUMPTION_REQUEST',
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

    public function test_update_status_with_message(): void
    {
        $app = App::factory()->create();

        $log = NotificationLog::factory()->create([
            'app_id' => $app->id,
            'payload' => json_encode([
                'notificationType' => 'CONSUMPTION_REQUEST',
                'notificationUUID' => 'test-uuid',
                'data' => [
                    'bundleId' => 'com.test.app',
                    'environment' => 'Sandbox',
                    'signedTransactionInfo' => [
                        'decoded' => [
                            'originalTransactionId' => '123456',
                            'transactionId' => '654321',
                        ],
                    ],
                ],
            ]),
        ]);

        $consumptionLog = $this->repository->storeLog($app, $log);

        $this->repository->updateStatus(
            $consumptionLog->original_transaction_id,
            ConsumptionLogStatusEnum::FAIL,
            'Test error message'
        );

        $consumptionLog->refresh();
        $this->assertEquals(ConsumptionLogStatusEnum::FAIL, $consumptionLog->status);
        $this->assertEquals('Test error message', $consumptionLog->status_msg);
    }
}
