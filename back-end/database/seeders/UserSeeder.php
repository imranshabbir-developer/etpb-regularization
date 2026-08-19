<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Bootstrap accounts — one per statutory and operational role.
 *
 * Every account is created with force_password_change = true, so the seeded
 * password cannot survive first login. These are commissioning accounts, not
 * production credentials.
 */
class UserSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'Etpb@2026#Change';

    public function run(): void
    {
        $lahore = DB::table('districts')->where('name', 'Lahore')->value('id');
        $lahoreOffice = DB::table('offices')->where('code', 'ETPB-HO')->value('id');
        $lahoreDistrictOffice = DB::table('offices')
            ->where('name', 'ETPB District Office, Lahore')->value('id');

        $accounts = [
            ['System Administrator', 'admin@etpb.gov.pk',    '3520112345671', 'System Administrator',   'SYSTEM_ADMIN',      $lahoreOffice,         null],
            ['Chairman ETPB',        'chairman@etpb.gov.pk', '3520112345672', 'Chairman',               'CHAIRMAN',          $lahoreOffice,         null],
            ['Administrator Lahore', 'admin.lhr@etpb.gov.pk','3520112345673', 'Administrator',          'ADMINISTRATOR',     $lahoreOffice,         $lahore],
            ['District Officer Lahore','do.lhr@etpb.gov.pk', '3520112345674', 'Deputy Administrator',   'DISTRICT_OFFICER',  $lahoreDistrictOffice, $lahore],
            ['Dealing Assistant',    'da.lhr@etpb.gov.pk',   '3520112345675', 'Dealing Assistant',      'DEALING_ASSISTANT', $lahoreDistrictOffice, $lahore],
            ['Accounts Officer',     'accounts.lhr@etpb.gov.pk','3520112345676','Accounts Officer',     'ACCOUNTS_OFFICER',  $lahoreDistrictOffice, $lahore],
            ['Legal Officer',        'legal.lhr@etpb.gov.pk','3520112345677', 'Legal Officer',          'LEGAL_OFFICER',     $lahoreDistrictOffice, $lahore],
            ['Internal Auditor',     'audit@etpb.gov.pk',    '3520112345678', 'Auditor',                'AUDITOR',           $lahoreOffice,         null],
        ];

        foreach ($accounts as [$name, $email, $cnic, $designation, $roleCode, $officeId, $districtId]) {
            $userId = DB::table('users')->insertGetId([
                'name'                  => $name,
                'email'                 => $email,
                'cnic'                  => $cnic,
                'designation'           => $designation,
                'contact'               => '042-99200000',
                'office_id'             => $officeId,
                'district_id'           => $districtId,
                'status'                => 'ACTIVE',
                'password'              => Hash::make(self::DEFAULT_PASSWORD),
                'force_password_change' => true,
                'email_verified_at'     => now(),
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            $roleId = DB::table('roles')->where('code', $roleCode)->value('id');
            DB::table('user_role')->insert([
                'user_id'     => $userId,
                'role_id'     => $roleId,
                'assigned_at' => now(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $this->command->newLine();
        $this->command->warn('  Seeded ' . count($accounts) . ' commissioning accounts.');
        $this->command->warn('  Default password for all: ' . self::DEFAULT_PASSWORD);
        $this->command->warn('  Every account must change its password at first login.');
        $this->command->newLine();
    }
}
