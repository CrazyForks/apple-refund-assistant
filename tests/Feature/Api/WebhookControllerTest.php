<?php

namespace Tests\Feature\Api;

use App\Enums\AppStatusEnum;
use App\Enums\EnvironmentEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\App;
use App\Services\IapService;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AppleSignedPayload;
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

        $payload = [
            'notificationType' => 'TEST',
            'subtype' => null,
            'notificationUUID' => 'uuid-123',
            'version' => '2.0',
            'signedDate' => 1690000000000,
            // AppMetadata 需要的字段（至少包含 bundleId 等）
            'bundleId' => 'com.example.test',
            'bundleVersion' => '1.0.0',
            // 其它 AppMetadata 字段
            'environment' => EnvironmentEnum::SANDBOX->value,
        ];
        $signedPayload = AppleSignedPayload::buildResponseBodyV2FromArray(NotificationTypeEnum::TEST->value, $payload);
        $this->mock(IapService::class, function ($mock) use ($signedPayload) {
            $mock->shouldReceive('decodePayload')
                ->andReturn($signedPayload);
        });
        // 模拟请求
        $response = $this->postJson("api/v1/apps/{$app->id}/webhook", []);
        // 验证结果
        $response->assertSuccessful();
        $response->assertSeeText('SUCCESS');

        // 验证应用状态已更新
        $this->assertDatabaseHas('apps', [
            'id' => $app->id,
            'status' => AppStatusEnum::NORMAL,
        ]);
    }
}
