<?php

namespace Database\Seeders;

use App\Models\Operator;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('PANEL_ADMIN_EMAIL');
        $password = env('PANEL_ADMIN_PASSWORD');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            return;
        }

        Operator::query()->firstOrCreate(
            ['email' => $email],
            ['name' => 'Administrador', 'password' => $password],
        );
    }
}
