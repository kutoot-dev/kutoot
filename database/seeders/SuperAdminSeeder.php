<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::updateOrCreate(
            ['email' => 'it@kutoot.com'],
            [
                'name' => 'Kutoot',
                'mobile' => '9000000001',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
            ]
        );

        $user->assignRole('Super Admin');
    }
}
