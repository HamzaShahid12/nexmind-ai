<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            PlanSeeder::class,
            AiToolSeeder::class,
            UserSeeder::class,
            FaqSeeder::class,
            BlogSeeder::class,
        ]);
    }
}
