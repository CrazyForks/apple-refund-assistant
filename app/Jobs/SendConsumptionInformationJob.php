<?php

namespace App\Jobs;

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
        // TODO SEND DATA
        Log::info("[{$this->log->notification_uuid}] send consumption info...");
    }
}
