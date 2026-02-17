<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]
        );

        $user->assignRole('Super Admin');
    }
}
