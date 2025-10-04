<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\AppleUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppleUserFactory extends Factory
{
    protected $model = AppleUser::class;

    public function definition(): array
    {
        return [
            'app_id' => App::factory(),
            'app_account_token' => $this->faker->uuid(),
            'purchased_dollars' => $this->faker->randomFloat(2, 0, 1000),
            'refunded_dollars' => $this->faker->randomFloat(2, 0, 100),
            'play_seconds' => $this->faker->numberBetween(0, 100000),
            'register_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}

