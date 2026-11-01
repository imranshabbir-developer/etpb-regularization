<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * November 2026 board meeting changes:
 *  - Drop applicant Clause-12 tickbox flags (indigent / widow / orphan)
 *  - Remission grounds no longer offer those three labels
 *  - Property types / usage aligned to Open Plot / Residential / Commercial / …
 *  - Property number optional; floors, open area, court name + judgment gist
 *  - Ghunta unit + URBAN (225) as system default
 *  - All document types optional; add possession certificate + payment receipt + assessment sheet
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- Applicants: remove remittance tickboxes ---------------------------
        if (Schema::hasColumn('applicants', 'is_indigent')) {
            Schema::table('applicants', function (Blueprint $t) {
                $t->dropColumn(['is_indigent', 'is_widow', 'is_orphan']);
            });
        }

        // ---- Remissions: map old grounds → OTHER, shrink enum ------------------
        if (Schema::hasTable('remissions')) {
            DB::table('remissions')
                ->whereIn('ground', ['INDIGENT', 'WIDOW', 'ORPHAN'])
                ->update(['ground' => 'OTHER']);

            DB::statement("ALTER TABLE remissions MODIFY COLUMN ground ENUM('INCAPABLE','OTHER') NOT NULL");
        }

        // ---- Properties: optional number, new enums, floors, survey ------------
        if (Schema::hasTable('properties')) {
            // Widen enums to VARCHAR, remap legacy codes, then lock the new ENUM.
            DB::statement("ALTER TABLE properties MODIFY COLUMN property_type VARCHAR(40) NOT NULL DEFAULT 'RESIDENTIAL'");
            DB::statement("ALTER TABLE properties MODIFY COLUMN usage_type VARCHAR(40) NOT NULL DEFAULT 'RESIDENTIAL'");

            DB::table('properties')->whereIn('property_type', ['HOUSE', 'BUILDING'])
                ->update(['property_type' => 'RESIDENTIAL']);
            DB::table('properties')->where('property_type', 'SHOP')
                ->update(['property_type' => 'COMMERCIAL']);
            DB::table('properties')->whereIn('property_type', ['PLOT', 'AGRI_LAND'])
                ->update(['property_type' => 'OPEN_PLOT']);
            DB::table('properties')
                ->whereNotIn('property_type', ['OPEN_PLOT', 'RESIDENTIAL', 'COMMERCIAL', 'RESIDENTIAL_CUM_COMMERCIAL', 'OTHER'])
                ->update(['property_type' => 'OTHER']);

            DB::table('properties')
                ->whereNotIn('usage_type', ['OPEN_PLOT', 'RESIDENTIAL', 'COMMERCIAL', 'RESIDENTIAL_CUM_COMMERCIAL', 'OTHER'])
                ->update(['usage_type' => 'OTHER']);

            DB::statement("ALTER TABLE properties MODIFY COLUMN property_type ENUM('OPEN_PLOT','RESIDENTIAL','COMMERCIAL','RESIDENTIAL_CUM_COMMERCIAL','OTHER') NOT NULL DEFAULT 'RESIDENTIAL'");
            DB::statement("ALTER TABLE properties MODIFY COLUMN usage_type ENUM('OPEN_PLOT','RESIDENTIAL','COMMERCIAL','RESIDENTIAL_CUM_COMMERCIAL','OTHER') NOT NULL DEFAULT 'RESIDENTIAL'");
            DB::statement('ALTER TABLE properties MODIFY COLUMN property_no VARCHAR(60) NULL');

            Schema::table('properties', function (Blueprint $t) {
                if (! Schema::hasColumn('properties', 'survey_no')) {
                    $t->string('survey_no', 60)->nullable()->after('khasra_no');
                }
                if (! Schema::hasColumn('properties', 'number_of_floors')) {
                    $t->unsignedTinyInteger('number_of_floors')->nullable()->after('boundaries');
                }
                if (! Schema::hasColumn('properties', 'floor_wise_covered_area')) {
                    $t->json('floor_wise_covered_area')->nullable()->after('number_of_floors');
                }
            });
        }

        // ---- Property areas: open area -----------------------------------------
        if (Schema::hasTable('property_areas') && ! Schema::hasColumn('property_areas', 'open_area_sqft')) {
            Schema::table('property_areas', function (Blueprint $t) {
                $t->decimal('open_area_sqft', 18, 4)->nullable()->after('covered_area_sqft');
            });
        }

        // ---- Possession: court name + judgment gist ----------------------------
        if (Schema::hasTable('possession_details')) {
            Schema::table('possession_details', function (Blueprint $t) {
                if (! Schema::hasColumn('possession_details', 'court_name')) {
                    $t->string('court_name', 200)->nullable()->after('date_of_judicial_verdict');
                }
                if (! Schema::hasColumn('possession_details', 'judgment_gist')) {
                    $t->text('judgment_gist')->nullable()->after('judicial_reference');
                }
            });
        }

        // ---- Unit profiles: URBAN default + Ghunta -----------------------------
        if (Schema::hasTable('unit_conversion_profiles')) {
            DB::table('unit_conversion_profiles')->update(['is_default' => false]);
            DB::table('unit_conversion_profiles')->where('code', 'URBAN')->update(['is_default' => true]);

            DB::table('settings')->where('key', 'default_unit_profile')->update([
                'value'       => 'URBAN',
                'description' => 'URBAN (1 Marla = 225 sqft) is the system default when the applicant does not specify a standard. '
                    . 'REVENUE (272.25) and GHUNTA (1089) remain available.',
                'updated_at'  => now(),
            ]);

            // Ghunta profile itself is owned by ReferenceDataSeeder on fresh
            // installs. This migration only backfills live databases that were
            // seeded before Ghunta existed.
            $ghuntaId = DB::table('unit_conversion_profiles')->where('code', 'GHUNTA')->value('id');
            if (! $ghuntaId && DB::table('unit_conversion_profiles')->where('code', 'URBAN')->exists()) {
                $ghuntaId = DB::table('unit_conversion_profiles')->insertGetId([
                    'code'        => 'GHUNTA',
                    'name'        => '1 Ghunta (1089 sqft)',
                    'description' => 'Ghunta-based measurement. 1 Ghunta = 1089 sqft. '
                        . 'Also exposes Marla at 225 sqft for mixed entry.',
                    'is_default'  => false,
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                $units = [
                    ['SQFT', 'Square Foot', 'مربع فٹ', '1.0000', 1, false],
                    ['SQYD', 'Square Yard', 'گز', '9.0000', 2, false],
                    ['MARLA', 'Marla', 'مرلہ', '225.0000', 3, true],
                    ['GHUNTA', 'Ghunta', 'گھنٹہ', '1089.0000', 4, true],
                    ['KANAL', 'Kanal', 'کنال', '4500.0000', 5, true],
                    ['ACRE', 'Acre', 'ایکڑ', '43560.0000', 6, true],
                ];
                foreach ($units as [$code, $name, $ur, $sqft, $order, $compound]) {
                    DB::table('unit_conversion_factors')->insert([
                        'unit_profile_id'       => $ghuntaId,
                        'unit_code'             => $code,
                        'unit_name'             => $name,
                        'unit_name_ur'          => $ur,
                        'sqft_per_unit'         => $sqft,
                        'display_order'         => $order,
                        'is_compound_component' => $compound,
                        'is_active'             => true,
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ]);
                }
            }

            // Add GHUNTA factor to existing REVENUE + URBAN profiles if missing.
            foreach (['REVENUE', 'URBAN'] as $code) {
                $pid = DB::table('unit_conversion_profiles')->where('code', $code)->value('id');
                if (! $pid) {
                    continue;
                }
                $exists = DB::table('unit_conversion_factors')
                    ->where('unit_profile_id', $pid)
                    ->where('unit_code', 'GHUNTA')
                    ->exists();
                if (! $exists) {
                    $maxOrder = (int) DB::table('unit_conversion_factors')
                        ->where('unit_profile_id', $pid)->max('display_order');
                    DB::table('unit_conversion_factors')->insert([
                        'unit_profile_id'       => $pid,
                        'unit_code'             => 'GHUNTA',
                        'unit_name'             => 'Ghunta',
                        'unit_name_ur'          => 'گھنٹہ',
                        'sqft_per_unit'         => '1089.0000',
                        'display_order'         => $maxOrder + 1,
                        'is_compound_component' => false,
                        'is_active'             => true,
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ]);
                }
            }
        }

        // ---- Document types: all optional + new types --------------------------
        if (Schema::hasTable('document_types')) {
            DB::table('document_types')->update(['is_mandatory' => false, 'updated_at' => now()]);

            // Only backfill on already-seeded databases. Fresh installs get these
            // from ReferenceDataSeeder after migrations complete.
            if (DB::table('document_types')->count() === 0) {
                return;
            }

            $extras = [
                [
                    'code' => 'POSSESSION_CERTIFICATE',
                    'name' => 'Possession certificate by Revenue Officer / Tehsildar / Mukhtiarkar',
                    'name_ur' => 'قبضہ سرٹیفکیٹ',
                    'category' => 'REVENUE_RECORD',
                    'order' => 16,
                    'proves' => true,
                ],
                [
                    'code' => 'PAYMENT_RECEIPT',
                    'name' => 'Payment Receipt (Rs. 5,000 / ETPB payment)',
                    'name_ur' => 'ادائیگی کی رسید',
                    'category' => 'FINANCIAL',
                    'order' => 17,
                    'proves' => false,
                ],
                [
                    'code' => 'ASSESSMENT_SHEET',
                    'name' => 'Rent Assessment Sheet',
                    'name_ur' => 'کرایہ تشخیص شیٹ',
                    'category' => 'FINANCIAL',
                    'order' => 18,
                    'proves' => false,
                ],
            ];

            foreach ($extras as $row) {
                if (DB::table('document_types')->where('code', $row['code'])->exists()) {
                    continue;
                }
                DB::table('document_types')->insert([
                    'code'                       => $row['code'],
                    'name'                       => $row['name'],
                    'name_ur'                    => $row['name_ur'],
                    'category'                   => $row['category'],
                    'is_certified_copy_required' => false,
                    'is_mandatory'               => false,
                    'is_waivable'                => true,
                    'proves_possession_date'     => $row['proves'],
                    'display_order'              => $row['order'],
                    'is_active'                  => true,
                    'created_at'                 => now(),
                    'updated_at'                 => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive down is intentionally limited — re-adding dropped
        // applicant flags would not restore historical tickbox data.
        if (Schema::hasTable('applicants') && ! Schema::hasColumn('applicants', 'is_indigent')) {
            Schema::table('applicants', function (Blueprint $t) {
                $t->boolean('is_indigent')->default(false);
                $t->boolean('is_widow')->default(false);
                $t->boolean('is_orphan')->default(false);
            });
        }
    }
};
