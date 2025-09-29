<?php

namespace App\Jobs;

use App\Models\App;
use App\Models\NotificationRawLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendRequestToAppNotificationUrlJob implements ShouldQueue
{
    use Queueable;

    public NotificationRawLog $log;
    public App $app;
    /**
     * Create a new job instance.
     */
    public function __construct(NotificationRawLog $log, App $app)
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

        $appUrl = $this->app->notification_url;
        $body = $log->request_body ?? '';
        if (! empty($appUrl) && ! empty($body)) {
            try {
                $resp = Http::timeout(config('notification.timeout'))
                    ->contentType('application/json')
                    ->post($appUrl, $body);
                $log->forward_success = $resp->ok();
                $log->forward_msg = $this->trimBody($resp->body());
                $log->save();
            } catch (\Exception $e) {
                $log->forward_success = false;
                $log->forward_msg = $this->trimBody($e->getMessage());
                $log->save();
            }
        }
    }

    protected function trimBody($body)
    {
        return mb_substr($body, 0, 100);
    }
}
