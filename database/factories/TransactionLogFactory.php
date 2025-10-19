<?php

namespace Database\Factories;

use App\Enums\EnvironmentEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\App;
use App\Models\TransactionLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionLogFactory extends Factory
{
    protected $model = TransactionLog::class;

    public function definition(): array
    {
        return [
            'app_id' => App::factory(),
            'notification_uuid' => $this->faker->uuid(),
            'notification_type' => NotificationTypeEnum::SUBSCRIBED->value,
            'bundle_id' => 'com.test.'.$this->faker->slug(2),
            'environment' => EnvironmentEnum::SANDBOX->value,
            'original_transaction_id' => $this->faker->numerify('##########'),
            'transaction_id' => $this->faker->numerify('##########'),
            'purchase_date' => $this->faker->unixTime(),
            'price' => $this->faker->randomFloat(2, 0, 100),
            'currency' => 'USD',
            'app_account_token' => $this->faker->uuid(),
            'product_id' => 'com.test.product.'.$this->faker->randomNumber(2),
            'product_type' => 'Auto-Renewable Subscription',
            'original_purchase_date' => $this->faker->unixTime('-1 year'),
            'expiration_date' => $this->faker->unixTime('+1 year'),
            'in_app_ownership_type' => 'PURCHASED',
            'quantity' => 1,
        ];
    }
}
