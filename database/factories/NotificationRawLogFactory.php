<?php

namespace Database\Factories;

use App\Models\NotificationRawLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationRawLog>
 */
class NotificationRawLogFactory extends Factory
{
    protected $model = NotificationRawLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'request_body' => json_encode(['test' => 'data']),
            'forward_msg' => null,
        ];
    }
}
