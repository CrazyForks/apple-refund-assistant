<?php

namespace App\Console\Commands;

use App\Enums\EnvironmentEnum;
use App\Enums\NotificationLogStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\App;
use App\Models\AppleUser;
use App\Models\ConsumptionLog;
use App\Models\NotificationLog;
use App\Models\NotificationRawLog;
use App\Models\RefundLog;
use App\Models\TransactionLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetApp extends Command
{
    protected $signature = 'app:reset';

    protected $description = 'reset app for demo';

    public function handle()
    {
        $count = 500;

        $this->call('migrate:fresh', ['--force' => true]);
        $this->call('db:seed', ['--force' => true]);
        $this->info("Starting to seed {$count} records per table...");
        // Seed Apps
        $this->info('Seeding Apps...');
        $apps = App::factory($count / 100)->create();

        // Seed Apple Users
        $this->info('Seeding Apple Users...');
        AppleUser::factory($count / 100)->create([
            'app_id' => fn() => $apps->random()->id,
        ]);

        // Seed Transaction Logs
        $this->info('Seeding Transaction Logs...');
        TransactionLog::factory($count)->create([
            'app_id' => fn() => $apps->random()->id,
        ]);

        // Seed Refund Logs
        $this->info('Seeding Refund Logs...');
        RefundLog::factory($count)->create([
            'app_id' => fn() => $apps->random()->id,
        ]);

        // Seed Consumption Logs
        $this->info('Seeding Consumption Logs...');
        ConsumptionLog::factory($count)->create([
            'app_id' => fn() => $apps->random()->id,
        ]);

        // Seed Notification Logs with Raw Logs
        $this->info('Seeding Notification Logs with Raw Logs...');
        for ($i = 0; $i < $count; $i++) {
            $app = $apps->random();

            $notificationLog = NotificationLog::create([
                'app_id' => $app->id,
                'notification_uuid' => fake()->uuid(),
                'notification_type' => fake()->randomElement(NotificationTypeEnum::cases())->value,
                'bundle_id' => $app->bundle_id,
                'bundle_version' => fake()->numerify('##.#.#'),
                'environment' => fake()->randomElement(EnvironmentEnum::cases())->value,
                'payload' => json_encode([
                    'notificationType' => 'TEST',
                    'data' => fake()->words(10, true),
                ]),
                'status' => fake()->randomElement(NotificationLogStatusEnum::cases())->value,
                'forward_success' => fake()->boolean(70) ? 1 : 0,
            ]);

            NotificationRawLog::create([
                'id' => $notificationLog->id,
                'request_body' => json_encode([
                    'signedPayload' => fake()->sha256(),
                    'timestamp' => now()->timestamp,
                    'data' => fake()->paragraph(),
                ]),
            ]);
        }

        $this->info('✓ Test data seeding completed successfully!');
        $this->table(
            ['Table', 'Records'],
            [
                ['Users', $count / 100],
                ['Apps', $count / 100],
                ['Apple Users', $count],
                ['Transaction Logs', $count],
                ['Refund Logs', $count],
                ['Consumption Logs', $count],
                ['Notification Logs', $count],
                ['Notification Raw Logs', $count],
            ]
        );
    }
}
