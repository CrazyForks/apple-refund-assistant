<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $exists = User::query()->where('email', 'admin@dev.com')->exists();
        if ($exists) {
            $this->command->warn('admin exists');

            return;
        }

        User::create([
            'name' => 'admin',
            'email' => 'admin@dev.com',
            'password' => Hash::make('admin'),
        ]);
    }
}
