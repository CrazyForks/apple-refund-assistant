<?php

namespace App\Jobs;

use App\Enums\NotificationLogStatusEnum;
use App\Models\App;
use App\Models\NotificationLog;
use App\Models\NotificationRawLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FinishNotificationJob implements ShouldQueue
{
    use Queueable;

    public NotificationLog $log;
    public App $app;
    /**
     * Create a new job instance.
     */
    public function __construct(NotificationLog $log, App $app)
    {
        $this->log = $log;
        $this->app = $app;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = $this->log;
        $log->status = NotificationLogStatusEnum::PROCESSED;

        // no handle
        $appUrl = $this->app->notification_url;
        /**
         * @var $raw NotificationRawLog
         */
        $raw = $log->raw()->first();
        $body = $raw->request_body ?? '';

        $msg = null;
        if (! empty($appUrl) && !empty($body)) {
            try {
                $resp = Http::timeout(config('notification.timeout', 30))
                    ->withBody($body, 'application/json')
                    ->post($appUrl);
                $log->forward_success = $resp->successful();
                $msg = $resp->body();
            } catch (\Exception $e) {
                $log->forward_success = false;
                $msg = $e->getMessage();
            }
        }

        $log->save();

        if (! empty($msg)) {
            NotificationRawLog::query()
                ->where('id', $log->id)
                ->update(['forward_msg' => Str::substr($msg, 0, 191)]);
        }
    }
}
