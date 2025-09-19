<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as LaravelCommand;

class InitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'init app';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $envFile = base_path('.env');
        if (! is_file($envFile)) {
            $this->error("[{$envFile}] file is don't exists, copy .env.example .env");
            return LaravelCommand::FAILURE;
        }


        if (config('database')['default'] === 'sqlite') {
            $this->warn('you can use mysql/pgsql, sqlite db is in local');
        }

        // 1. migration
        $this->info('run database migrate');
        $this->call('migrate', ['--force' => true]);

        // 2. run database seed
        $email = 'admin@dev.com';
        if (! User::query()->where('email', $email)->exists()) {
            $password = 'admin';
            $this->alert("create admin user [{$email}/{$password}]");
            $this->call('make:filament-user', ['--name' => 'admin', '--email' => $email, '--password' => $password]);
        }

        $this->info('all set');
    }
}
