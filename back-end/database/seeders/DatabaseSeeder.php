<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Full commissioning dataset for a fresh laptop / demo install.
 *
 * Order matters: reference and geography first, then RBAC and users, then the
 * officer-queue demo cases, then the public-applicant owned cases.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReferenceDataSeeder::class,
            GeographySeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            ApplicantAccountSeeder::class,
            DemoDataSeeder::class,
            PublicApplicantCaseSeeder::class,
        ]);
    }
}
