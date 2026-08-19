<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Public applicant accounts.
 *
 * These are members of the public, not officers, so they are kept apart from
 * UserSeeder: they hold no office, belong to no district establishment, and are
 * not forced to change their password, because a member of the public chooses
 * their own when they register at /register. These exist so the citizen-facing
 * side of the portal can be signed into and shown without registering first.
 *
 * Each account also gets a record in `applicants`, holding the particulars the
 * Scheme asks for — name, parentage, CNIC and postal address. The wizard reads
 * it and offers those details back rather than making a person type again what
 * the Board already holds.
 *
 * The seeder is safe to run repeatedly: it matches on the email address and the
 * CNIC, and updates rather than duplicating.
 */
class ApplicantAccountSeeder extends Seeder
{
    public function run(): void
    {
        $applicantRole = DB::table('roles')->where('code', 'APPLICANT')->value('id');
        $lahore        = DB::table('districts')->where('name', 'Lahore')->value('id');

        $people = [
            [
                'name'      => 'Imran Shabbir',
                'email'     => 'imran.shabbir@example.com',
                'password'  => 'Imran@Portal2026',
                'cnic'      => '3520187654321',
                'contact'   => '0300-4567890',
                'parentage_type' => 'FATHER',
                'parentage_name' => 'Shabbir Hussain',
                'address'   => 'Awan Town, Lahore',
                'district'  => $lahore,
            ],
            [
                'name'      => 'Demo Applicant',
                'email'     => 'demo.applicant@example.com',
                'password'  => 'Demo#Portal2026',
                'cnic'      => '3520199000099',
                'contact'   => '0300-1112223',
                'parentage_type' => 'FATHER',
                'parentage_name' => 'Demo Father',
                'address'   => 'Model Town, Lahore',
                'district'  => $lahore,
            ],
        ];

        foreach ($people as $p) {
            $userId = DB::table('users')->where('email', $p['email'])->value('id');

            $attributes = [
                'name'                  => $p['name'],
                'cnic'                  => $p['cnic'],
                'contact'               => $p['contact'],
                'designation'           => 'Applicant',
                'office_id'             => null,
                'district_id'           => null,
                'status'                => 'ACTIVE',
                // Chosen by the person, so there is nothing to force a change of.
                'force_password_change' => false,
                'password_changed_at'   => now(),
                'email_verified_at'     => now(),
                'updated_at'            => now(),
            ];

            if ($userId) {
                DB::table('users')->where('id', $userId)->update($attributes);
            } else {
                $userId = DB::table('users')->insertGetId($attributes + [
                    'email'      => $p['email'],
                    'password'   => Hash::make($p['password']),
                    'created_at' => now(),
                ]);
            }

            // Only set the password when the account is new, so a password the
            // person has since changed is never quietly reset by a re-seed.
            if (! DB::table('users')->where('id', $userId)->value('password')) {
                DB::table('users')->where('id', $userId)
                    ->update(['password' => Hash::make($p['password'])]);
            }

            if ($applicantRole) {
                DB::table('user_role')->updateOrInsert(
                    ['user_id' => $userId, 'role_id' => $applicantRole],
                    ['assigned_at' => now(), 'created_at' => now(), 'updated_at' => now()],
                );
            }

            // The particulars on file. Matched on CNIC, which is the identifier
            // the Board itself works from.
            $existing = DB::table('applicants')
                ->where('cnic', $p['cnic'])
                ->whereNull('deleted_at')
                ->value('id');

            $particulars = [
                'user_id'             => $userId,
                'full_name'           => $p['name'],
                'parentage_type'      => $p['parentage_type'],
                'parentage_name'      => $p['parentage_name'],
                'contact'             => $p['contact'],
                'email'               => $p['email'],
                'postal_address'      => $p['address'],
                'address_district_id' => $p['district'],
                'created_by'          => $userId,
                'updated_at'          => now(),
            ];

            if ($existing) {
                DB::table('applicants')->where('id', $existing)->update($particulars);
            } else {
                DB::table('applicants')->insert($particulars + [
                    'cnic'       => $p['cnic'],
                    'created_at' => now(),
                ]);
            }
        }

        $this->command?->newLine();
        $this->command?->warn('  Seeded ' . count($people) . ' public applicant accounts:');
        foreach ($people as $p) {
            $this->command?->warn(sprintf('    %-30s %s', $p['email'], $p['password']));
        }
        $this->command?->newLine();
    }
}
