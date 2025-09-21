<?php

namespace App\Jobs;

use App\Enums\ConsumptionLogStatusEnum;
use App\Models\ConsumptionLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendConsumptionInformationJob implements ShouldQueue
{
    use Queueable;

    public ConsumptionLog $log;
    /**
     * Create a new job instance.
     */
    public function __construct(ConsumptionLog $log)
    {
        //
        $this->log = $log;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $model = $this->log;
        // TODO SEND DATA
        Log::info("[{$model->notification_uuid}] send consumption info...");
        $model->status = ConsumptionLogStatusEnum::SUCCESS;
        $model->save();
    }
}
