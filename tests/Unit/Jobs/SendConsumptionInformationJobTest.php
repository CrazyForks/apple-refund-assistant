<?php

namespace Tests\Unit\Jobs;

use App\Dao\AppDao;
use App\Enums\ConsumptionLogStatusEnum;
use App\Enums\EnvironmentEnum;
use App\Jobs\SendConsumptionInformationJob;
use App\Models\App;
use App\Models\ConsumptionLog;
use App\Services\ConsumptionService;
use App\Services\IapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendConsumptionInformationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_consumption_information_successfully(): void
    {
        $app = App::factory()->create();
        $log = ConsumptionLog::factory()->create([
            'app_id' => $app->id,
            'status' => ConsumptionLogStatusEnum::PENDING,
            'transaction_id' => 'trans-123',
            'environment' => EnvironmentEnum::SANDBOX->value,
        ]);

        $requestData = ['consumptionStatus' => 1];

        $consumptionService = \Mockery::mock(ConsumptionService::class);
        $consumptionService->shouldReceive('makeConsumptionRequest')
            ->with($log)
            ->once()
            ->andReturn($requestData);

        $iapService = \Mockery::mock(IapService::class);
        $iapService->shouldReceive('sendConsumptionInformation')
            ->with($app, 'trans-123', $requestData, EnvironmentEnum::SANDBOX->value)
            ->once();

        $appDao = \Mockery::mock(AppDao::class);
        $appDao->shouldReceive('find')
            ->with($app->id)
            ->once()
            ->andReturn($app);

        $job = new SendConsumptionInformationJob($log);
        $job->handle($consumptionService, $iapService, $appDao);

        $log->refresh();
        $this->assertEquals(ConsumptionLogStatusEnum::SUCCESS, $log->status);
        $this->assertEquals($requestData, $log->send_body);
    }

    public function test_handle_marks_as_failed_on_exception(): void
    {
        $app = App::factory()->create();
        $log = ConsumptionLog::factory()->create([
            'app_id' => $app->id,
            'status' => ConsumptionLogStatusEnum::PENDING,
            'transaction_id' => 'trans-456',
            'environment' => EnvironmentEnum::SANDBOX->value,
        ]);

        $consumptionService = \Mockery::mock(ConsumptionService::class);
        $consumptionService->shouldReceive('makeConsumptionRequest')
            ->andThrow(new \Exception('API Error'));

        $iapService = \Mockery::mock(IapService::class);

        $appDao = \Mockery::mock(AppDao::class);
        $appDao->shouldReceive('find')
            ->andReturn($app);

        $job = new SendConsumptionInformationJob($log);
        $job->handle($consumptionService, $iapService, $appDao);

        $log->refresh();
        $this->assertEquals(ConsumptionLogStatusEnum::FAIL, $log->status);
        $this->assertEquals('API Error', $log->status_msg);
    }

    public function test_handle_saves_request_body_before_sending(): void
    {
        $app = App::factory()->create();
        $log = ConsumptionLog::factory()->create([
            'app_id' => $app->id,
            'status' => ConsumptionLogStatusEnum::PENDING,
            'transaction_id' => 'trans-789',
            'environment' => EnvironmentEnum::PRODUCTION->value,
        ]);

        $requestData = ['consumptionStatus' => 1, 'customerConsented' => true];

        $consumptionService = \Mockery::mock(ConsumptionService::class);
        $consumptionService->shouldReceive('makeConsumptionRequest')
            ->andReturn($requestData);

        $iapService = \Mockery::mock(IapService::class);
        $iapService->shouldReceive('sendConsumptionInformation')->once();

        $appDao = \Mockery::mock(AppDao::class);
        $appDao->shouldReceive('find')->andReturn($app);

        $job = new SendConsumptionInformationJob($log);
        $job->handle($consumptionService, $iapService, $appDao);

        $log->refresh();
        $this->assertEquals($requestData, $log->send_body);
    }

    public function test_handle_uses_correct_environment(): void
    {
        $app = App::factory()->create();
        $log = ConsumptionLog::factory()->create([
            'app_id' => $app->id,
            'status' => ConsumptionLogStatusEnum::PENDING,
            'transaction_id' => 'trans-prod',
            'environment' => EnvironmentEnum::PRODUCTION->value,
        ]);

        $requestData = ['consumptionStatus' => 1];

        $consumptionService = \Mockery::mock(ConsumptionService::class);
        $consumptionService->shouldReceive('makeConsumptionRequest')
            ->andReturn($requestData);

        $iapService = \Mockery::mock(IapService::class);
        $iapService->shouldReceive('sendConsumptionInformation')
            ->withArgs(function ($appArg, $transId, $data, $env) {
                return $env === EnvironmentEnum::PRODUCTION->value;
            })
            ->once();

        $appDao = \Mockery::mock(AppDao::class);
        $appDao->shouldReceive('find')->andReturn($app);

        $job = new SendConsumptionInformationJob($log);
        $job->handle($consumptionService, $iapService, $appDao);

        $log->refresh();
        $this->assertEquals(ConsumptionLogStatusEnum::SUCCESS, $log->status);
    }
}

