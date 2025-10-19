<?php

namespace Database\Factories;

use App\Enums\ConsumptionLogStatusEnum;
use App\Enums\EnvironmentEnum;
use App\Models\App;
use App\Models\ConsumptionLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsumptionLogFactory extends Factory
{
    protected $model = ConsumptionLog::class;

    public function definition(): array
    {
        return [
            'app_id' => App::factory(),
            'app_account_token' => $this->faker->uuid(),
            'original_transaction_id' => $this->faker->numerify('##########'),
            'transaction_id' => $this->faker->numerify('##########'),
            'notification_uuid' => $this->faker->uuid(),
            'bundle_id' => 'com.test.'.$this->faker->slug(2),
            'environment' => EnvironmentEnum::SANDBOX->value,
            'consumption_request_reason' => 'UNINTENDED_PURCHASE',
            'deadline_at' => $this->faker->unixTime('+12 hours'),
            'status' => ConsumptionLogStatusEnum::PENDING->value,
            'status_msg' => null,
        ];
    }
}
