<?php

namespace Database\Factories;

use App\Enums\AppStatusEnum;
use App\Models\App;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppFactory extends Factory
{
    protected $model = App::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'bundle_id' => 'com.test.' . $this->faker->slug(2),
            'status' => AppStatusEnum::NORMAL->value,
            'refund_count' => 0,
            'refund_dollars' => 0,
            'transaction_count' => 0,
            'transaction_dollars' => 0,
            'consumption_count' => 0,
            'consumption_dollars' => 0,
        ];
    }
}

