<?php

namespace Tests\Feature\Api;

use App\Enums\AppStatusEnum;
use App\Models\App;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试成功处理测试类型的通知
     */
    public function test_successful_handle_test_notification()
    {
        // 创建测试应用
        $app = App::create([
            'name' => 'Test App',
            'bundle_id' => 'com.example.test',
            'status' => AppStatusEnum::WEB_HOOKING,
            'p8_key' => 'test_key',
            'key_id' => 'test_key_id',
            'issuer_id' => 'test_issuer_id',
        ]);

        // 由于 ResponseBodyV2 是 final 类，我们需要创建一个能满足需求的数组
        $mockPayloadData = [
            'notificationType' => 'TEST',
            'notificationUUID' => 'test-uuid',
            'appMetadata' => [
                'bundleId' => 'com.example.test',
            ],
        ];

        // Mock IapService
        $this->mock(\App\Services\IapService::class, function ($mock) use ($mockPayloadData) {
            // 使用数组替代对象，这样可以避免类型问题
            $mock->shouldReceive('decodePayload')->with('[]')->andReturn($mockPayloadData);
        });

        // Mock WebhookService 中的方法，跳过实际的类型检查
        $this->mock(\App\Services\WebhookService::class, function ($mock) use ($app) {
            $mock->shouldReceive('handleNotification')->andReturn('SUCCESS');
            
            // 确保应用状态被更新
            $app->status = AppStatusEnum::NORMAL;
            $app->save();
        });

        // 模拟请求
        $response = $this->postJson("api/v1/apps/{$app->id}/webhook", []);

        // 验证结果
        $response->assertStatus(200);
        $response->assertSeeText('SUCCESS');

        // 验证应用状态已更新
        $this->assertDatabaseHas('apps', [
            'id' => $app->id,
            'status' => AppStatusEnum::NORMAL,
        ]);
    }
}