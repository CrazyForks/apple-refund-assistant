<?php

namespace Database\Factories;

use App\Enums\EnvironmentEnum;
use App\Models\App;
use App\Models\RefundLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RefundLog>
 */
class RefundLogFactory extends Factory
{
    protected $model = RefundLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'app_id' => App::factory(),
            'app_account_token' => $this->faker->uuid(),
            'notification_uuid' => $this->faker->uuid(),
            'bundle_id' => 'com.test.' . $this->faker->slug(2),
            'bundle_version' => $this->faker->numerify('##.#.#'),
            'environment' => EnvironmentEnum::SANDBOX->value,
            'purchase_date' => $this->faker->unixTime('-6 months'),
            'original_transaction_id' => $this->faker->numerify('##########'),
            'transaction_id' => $this->faker->numerify('##########'),
            'price' => $this->faker->randomFloat(2, 0, 100),
            'currency' => 'USD',
            'refund_date' => $this->faker->unixTime(),
            'refund_reason' => $this->faker->randomElement(['UNINTENDED_PURCHASE', 'APP_ISSUE', 'OTHER']),
        ];
    }
}
