<?php

namespace App\Jobs;

use App\Dao\AppDao;
use App\Enums\ConsumptionLogStatusEnum;
use App\Models\ConsumptionLog;
use App\Models\NotificationLog;
use App\Models\TransactionLog;
use App\Services\ConsumptionService;
use App\Services\IapService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Readdle\AppStoreServerAPI\Exception\AppStoreServerAPIException;

class SendConsumptionInformationJob implements ShouldQueue
{
    use Queueable;

    public ConsumptionLog $consumptionLog;

    /**
     * Create a new job instance.
     */
    public function __construct(ConsumptionLog $consumptionLog)
    {
        $this->consumptionLog = $consumptionLog;
    }

    /**
     * Execute the job.
     */
    public function handle(ConsumptionService $consumptionService, IapService $iapService, AppDao $appDao): void
    {
        $log = $this->consumptionLog;

        try {
            // Build consumption request
            $app = $appDao->find($log->app_id);
            $requestData = $consumptionService->makeConsumptionRequest($log);

            $log->send_body = $requestData;

            // Send to Apple using payload data
            $iapService->sendConsumptionInformation(
                $app,
                $log->transaction_id,
                $requestData,
                $log->getEnvironment(),
            );

            // Mark as success
            $log->status = ConsumptionLogStatusEnum::SUCCESS;
            $log->save();

        } catch (\Exception $e) {
            // Mark as failed
            $log->status = ConsumptionLogStatusEnum::FAIL;
            $log->status_msg = $e->getMessage();
            $log->save();
        }
    }
}
