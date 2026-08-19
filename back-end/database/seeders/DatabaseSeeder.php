<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReferenceDataSeeder::class,
            GeographySeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
        ]);
    }
}
