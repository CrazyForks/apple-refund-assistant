<?php

namespace Database\Factories;

use App\Enums\EnvironmentEnum;
use App\Enums\NotificationLogStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\App;
use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'app_id' => App::factory(),
            'bundle_id' => 'com.test.'.$this->faker->slug(2),
            'bundle_version' => $this->faker->numerify('##.#.#'),
            'environment' => EnvironmentEnum::SANDBOX->value,
            'notification_type' => NotificationTypeEnum::TEST->value,
            'notification_uuid' => $this->faker->uuid(),
            'status' => NotificationLogStatusEnum::PROCESSING->value,
            'forward_success' => false,
        ];
    }
}
