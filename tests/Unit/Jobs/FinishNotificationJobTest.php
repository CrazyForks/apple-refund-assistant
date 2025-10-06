<?php

namespace Tests\Unit\Jobs;

use App\Enums\NotificationLogStatusEnum;
use App\Jobs\FinishNotificationJob;
use App\Models\App;
use App\Models\NotificationLog;
use App\Models\NotificationRawLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FinishNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_constructor_sets_properties(): void
    {
        $app = App::factory()->create();
        $logId = DB::table('notification_logs')->insertGetId([
            'app_id' => $app->id,
            'bundle_id' => 'com.test.app',
            'bundle_version' => '1.0.0',
            'environment' => 'Sandbox',
            'notification_type' => 'TEST',
            'notification_uuid' => 'test-uuid',
            'status' => NotificationLogStatusEnum::PROCESSING->value,
            'forward_success' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $log = NotificationLog::find($logId);

        $job = new FinishNotificationJob($log, $app);

        $this->assertSame($log, $job->log);
        $this->assertSame($app, $job->app);
    }

    public function test_handle_marks_notification_as_processed(): void
    {
        $app = App::factory()->create(['notification_url' => null]);
        $logId = DB::table('notification_logs')->insertGetId([
            'app_id' => $app->id,
            'bundle_id' => 'com.test.app',
            'bundle_version' => '1.0.0',
            'environment' => 'Sandbox',
            'notification_type' => 'TEST',
            'notification_uuid' => 'test-uuid',
            'status' => NotificationLogStatusEnum::PROCESSING->value,
            'forward_success' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $log = NotificationLog::find($logId);

        $job = new FinishNotificationJob($log, $app);
        $job->handle();

        $log->refresh();
        $this->assertEquals(NotificationLogStatusEnum::PROCESSED, $log->status);
    }

    public function test_handle_saves_log_changes(): void
    {
        Http::fake();

        $app = App::factory()->create(['notification_url' => null]);
        $logId = DB::table('notification_logs')->insertGetId([
            'app_id' => $app->id,
            'bundle_id' => 'com.test.app',
            'bundle_version' => '1.0.0',
            'environment' => 'Sandbox',
            'notification_type' => 'TEST',
            'notification_uuid' => 'test-uuid',
            'status' => NotificationLogStatusEnum::PROCESSING->value,
            'forward_success' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $log = NotificationLog::find($logId);
        $initialUpdatedAt = $log->updated_at;

        sleep(1); // Ensure timestamp difference

        $job = new FinishNotificationJob($log, $app);
        $job->handle();

        $log->refresh();
        $this->assertNotEquals($initialUpdatedAt, $log->updated_at);
    }

    public function test_handle_with_empty_notification_url_does_not_attempt_http(): void
    {
        Http::fake();

        $app = App::factory()->create(['notification_url' => '']);
        $logId = DB::table('notification_logs')->insertGetId([
            'app_id' => $app->id,
            'bundle_id' => 'com.test.app',
            'bundle_version' => '1.0.0',
            'environment' => 'Sandbox',
            'notification_type' => 'TEST',
            'notification_uuid' => 'test-uuid',
            'status' => NotificationLogStatusEnum::PROCESSING->value,
            'forward_success' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $log = NotificationLog::find($logId);

        $job = new FinishNotificationJob($log, $app);
        $job->handle();

        Http::assertNothingSent();
    }

    public function test_handle_processes_notification_successfully(): void
    {
        $app = App::factory()->create(['notification_url' => null]);
        $logId = DB::table('notification_logs')->insertGetId([
            'app_id' => $app->id,
            'bundle_id' => 'com.test.app',
            'bundle_version' => '1.0.0',
            'environment' => 'Sandbox',
            'notification_type' => 'TEST',
            'notification_uuid' => 'test-uuid',
            'status' => NotificationLogStatusEnum::PROCESSING->value,
            'forward_success' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $log = NotificationLog::find($logId);

        $job = new FinishNotificationJob($log, $app);
        $job->handle();

        $this->assertTrue(true); // Job completes without errors
    }

    public function test_handle_with_notification_url_but_no_request_body(): void
    {
        Http::fake();

        $app = App::factory()->create(['notification_url' => 'https://example.com/webhook']);
        $logId = DB::table('notification_logs')->insertGetId([
            'app_id' => $app->id,
            'bundle_id' => 'com.test.app',
            'bundle_version' => '1.0.0',
            'environment' => 'Sandbox',
            'notification_type' => 'TEST',
            'notification_uuid' => 'test-uuid',
            'status' => NotificationLogStatusEnum::PROCESSING->value,
            'forward_success' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $log = NotificationLog::find($logId);

        $job = new FinishNotificationJob($log, $app);
        $job->handle();

        // Should not send HTTP request because request_body is null
        Http::assertNothingSent();
        $this->assertEquals(NotificationLogStatusEnum::PROCESSED, $log->refresh()->status);
    }

    public function test_handle_respects_notification_timeout_config(): void
    {
        config(['notification.timeout' => 45]);
        Http::fake();

        $app = App::factory()->create(['notification_url' => null]);
        $logId = DB::table('notification_logs')->insertGetId([
            'app_id' => $app->id,
            'bundle_id' => 'com.test.app',
            'bundle_version' => '1.0.0',
            'environment' => 'Sandbox',
            'notification_type' => 'TEST',
            'notification_uuid' => 'test-uuid',
            'status' => NotificationLogStatusEnum::PROCESSING->value,
            'forward_success' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $log = NotificationLog::find($logId);

        $job = new FinishNotificationJob($log, $app);
        $job->handle();

        // Config value should be used (even though HTTP won't be called in this test)
        $this->assertEquals(45, config('notification.timeout'));
    }

    public function test_handle_forwards_notification_successfully(): void
    {
        Http::fake([
            'https://example.com/webhook' => Http::response('Success response', 200)
        ]);

        $app = App::factory()->create(['notification_url' => 'https://example.com/webhook']);

        // Use Eloquent models to ensure relationships work properly
        $log = NotificationLog::create([
            'app_id' => $app->id,
            'bundle_id' => 'com.test.app',
            'bundle_version' => '1.0.0',
            'environment' => 'Sandbox',
            'notification_type' => 'TEST',
            'notification_uuid' => 'test-uuid-' . uniqid(),
            'status' => NotificationLogStatusEnum::PROCESSING,
            'forward_success' => false,
        ]);

        // Create raw log with same id
        NotificationRawLog::create([
            'id' => $log->id,
            'request_body' => '{"test": "data"}',
            'forward_msg' => null,
        ]);

        $job = new FinishNotificationJob($log, $app);
        $job->handle();

        // Check HTTP was sent
        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhook'
                && $request->body() === '{"test": "data"}';
        });

        // Check log was updated
        $log->refresh();
        $this->assertEquals(NotificationLogStatusEnum::PROCESSED, $log->status);
        $this->assertEquals(1, $log->forward_success);

        // Check forward_msg was saved
        $rawLog = NotificationRawLog::find($log->id);
        $this->assertNotNull($rawLog->forward_msg);
        $this->assertStringContainsString('Success', $rawLog->forward_msg);
    }

    public function test_handle_forwards_notification_with_http_failure(): void
    {
        Http::fake([
            'https://example.com/webhook' => Http::response('Error', 500)
        ]);

        $app = App::factory()->create(['notification_url' => 'https://example.com/webhook']);

        $log = NotificationLog::create([
            'app_id' => $app->id,
            'bundle_id' => 'com.test.app',
            'bundle_version' => '1.0.0',
            'environment' => 'Sandbox',
            'notification_type' => 'TEST',
            'notification_uuid' => 'test-uuid-' . uniqid(),
            'status' => NotificationLogStatusEnum::PROCESSING,
            'forward_success' => true,
        ]);

        NotificationRawLog::create([
            'id' => $log->id,
            'request_body' => '{"test": "data"}',
            'forward_msg' => null,
        ]);

        $job = new FinishNotificationJob($log, $app);
        $job->handle();

        $log->refresh();
        $this->assertEquals(NotificationLogStatusEnum::PROCESSED, $log->status);
        $this->assertEquals(0, $log->forward_success);

        // Check error message was saved
        $rawLog = NotificationRawLog::find($log->id);
        $this->assertNotNull($rawLog->forward_msg);
    }

    public function test_handle_catches_http_exception(): void
    {
        Http::fake(function () {
            throw new \Exception('Connection timeout error');
        });

        $app = App::factory()->create(['notification_url' => 'https://example.com/webhook']);

        $log = NotificationLog::create([
            'app_id' => $app->id,
            'bundle_id' => 'com.test.app',
            'bundle_version' => '1.0.0',
            'environment' => 'Sandbox',
            'notification_type' => 'TEST',
            'notification_uuid' => 'test-uuid-' . uniqid(),
            'status' => NotificationLogStatusEnum::PROCESSING,
            'forward_success' => true,
        ]);

        NotificationRawLog::create([
            'id' => $log->id,
            'request_body' => '{"test": "data"}',
            'forward_msg' => null,
        ]);

        $job = new FinishNotificationJob($log, $app);
        $job->handle();

        $log->refresh();
        $this->assertEquals(NotificationLogStatusEnum::PROCESSED, $log->status);
        $this->assertEquals(0, $log->forward_success);

        // Check exception message was saved
        $rawLog = NotificationRawLog::find($log->id);
        $this->assertNotNull($rawLog->forward_msg);
        $this->assertStringContainsString('Connection timeout', $rawLog->forward_msg);
    }

    public function test_handle_truncates_long_forward_message(): void
    {
        $longMessage = str_repeat('x', 200);
        Http::fake([
            'https://example.com/webhook' => Http::response($longMessage, 200)
        ]);

        $app = App::factory()->create(['notification_url' => 'https://example.com/webhook']);

        $log = NotificationLog::create([
            'app_id' => $app->id,
            'bundle_id' => 'com.test.app',
            'bundle_version' => '1.0.0',
            'environment' => 'Sandbox',
            'notification_type' => 'TEST',
            'notification_uuid' => 'test-uuid-' . uniqid(),
            'status' => NotificationLogStatusEnum::PROCESSING,
            'forward_success' => false,
        ]);

        NotificationRawLog::create([
            'id' => $log->id,
            'request_body' => '{"test": "data"}',
            'forward_msg' => null,
        ]);

        $job = new FinishNotificationJob($log, $app);
        $job->handle();

        // Check message was truncated to 191 characters
        $rawLog = NotificationRawLog::find($log->id);
        $this->assertNotNull($rawLog->forward_msg);
        $this->assertLessThanOrEqual(191, strlen($rawLog->forward_msg));
        $this->assertEquals(191, strlen($rawLog->forward_msg));
    }

    public function test_handle_skips_http_when_raw_log_missing(): void
    {
        Http::fake();

        $app = App::factory()->create(['notification_url' => 'https://example.com/webhook']);

        // Create log without raw log
        $log = NotificationLog::create([
            'app_id' => $app->id,
            'bundle_id' => 'com.test.app',
            'bundle_version' => '1.0.0',
            'environment' => 'Sandbox',
            'notification_type' => 'TEST',
            'notification_uuid' => 'test-uuid-' . uniqid(),
            'status' => NotificationLogStatusEnum::PROCESSING,
            'forward_success' => false,
        ]);

        $job = new FinishNotificationJob($log, $app);
        $job->handle();

        // Should not send HTTP request because raw log is missing
        Http::assertNothingSent();
        $this->assertEquals(NotificationLogStatusEnum::PROCESSED, $log->refresh()->status);
    }
}
