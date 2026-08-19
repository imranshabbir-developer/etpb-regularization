<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Unit conversion profiles, statutory settings, document types and rate sources.
 *
 * Nothing here is hard-coded in PHP: every statutory figure is a dated setting
 * so a fresh SRO can be absorbed by inserting a new row rather than editing code.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->unitProfiles();
        $this->settings();
        $this->documentTypes();
        $this->rateSources();
    }

    /**
     * A Marla is 272.25 sqft under the revenue (Kanal) system but 225 sqft in
     * most urban housing schemes. That 21% gap lands straight in the rent, so
     * the factor set is data and the profile used is stamped on each application.
     */
    private function unitProfiles(): void
    {
        $profiles = [
            [
                'code' => 'REVENUE',
                'name' => 'Revenue / Legal Standard (1 Marla = 272.25 sqft)',
                'description' => 'The Kanal-Marla system used in Pakistani revenue records. '
                    . '1 Acre = 8 Kanal = 160 Marla = 43,560 sqft.',
                'is_default' => true,
                'units' => [
                    ['SQFT',    'Square Foot',  'مربع فٹ', '1.0000',       1, false],
                    ['SQYD',    'Square Yard',  'گز',      '9.0000',       2, false],
                    ['SARSAI',  'Sarsai',       'سرسائی',  '30.2500',      3, true],
                    ['MARLA',   'Marla',        'مرلہ',    '272.2500',     4, true],
                    ['KANAL',   'Kanal',        'کنال',    '5445.0000',    5, true],
                    ['ACRE',    'Acre (Killa)', 'ایکڑ',    '43560.0000',   6, true],
                    ['MURABBA', 'Murabba',      'مربع',    '1089000.0000', 7, false],
                ],
            ],
            [
                'code' => 'URBAN',
                'name' => 'Urban / Housing Society Standard (1 Marla = 225 sqft)',
                'description' => 'The 25-square-yard Marla used by most urban housing schemes. '
                    . '1 Kanal = 20 Marla = 4,500 sqft. The Acre is unchanged at 43,560 sqft.',
                'is_default' => false,
                'units' => [
                    ['SQFT',  'Square Foot', 'مربع فٹ', '1.0000',     1, false],
                    ['SQYD',  'Square Yard', 'گز',      '9.0000',     2, false],
                    ['MARLA', 'Marla',       'مرلہ',    '225.0000',   3, true],
                    ['KANAL', 'Kanal',       'کنال',    '4500.0000',  4, true],
                    ['ACRE',  'Acre',        'ایکڑ',    '43560.0000', 5, true],
                ],
            ],
        ];

        foreach ($profiles as $p) {
            $id = DB::table('unit_conversion_profiles')->insertGetId([
                'code'        => $p['code'],
                'name'        => $p['name'],
                'description' => $p['description'],
                'is_default'  => $p['is_default'],
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            foreach ($p['units'] as [$code, $name, $nameUr, $sqft, $order, $compound]) {
                DB::table('unit_conversion_factors')->insert([
                    'unit_profile_id'       => $id,
                    'unit_code'             => $code,
                    'unit_name'             => $name,
                    'unit_name_ur'          => $nameUr,
                    'sqft_per_unit'         => $sqft,
                    'display_order'         => $order,
                    'is_compound_component' => $compound,
                    'is_active'             => true,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }
        }
    }

    private function settings(): void
    {
        $feeNote = 'Payable by pay order, banker cheque or demand draft in favour of Chairman ETPB.';

        $rows = [
            ['possession_cutoff_date', '2009-12-31', 'DATE', 'eligibility',
             'Possession cut-off date', 'Possession must be prior to 01-01-2010.',
             'Scheme 1977, Clause 3(ii)(a)', false],

            ['arrears_base_date', '2000-07-01', 'DATE', 'arrears',
             'Arrears statutory base date',
             'Arrears run from 01-07-2000, the date of occupation, or the date of judicial '
             . 'verdict - whichever is earlier.',
             'Scheme 1977, Clause 3(ii)(b)', false],

            ['assessment_base_date', '2006-07-01', 'DATE', 'assessment',
             'Assessment base date',
             'Assessment / re-assessment is made with effect from 01-07-2006.',
             'Scheme 1977, Clause 10(i)', false],

            ['enhancement_rate', '8.00', 'DECIMAL', 'assessment',
             'Annual rent enhancement rate (%)',
             'Enhancement in rent at eight per cent per annum.',
             'Scheme 1977, Clause 11(ii)', false],

            ['enhancement_method', 'COMPOUND', 'STRING', 'assessment',
             'Enhancement method',
             'SIMPLE or COMPOUND. The Scheme does not specify; over 24 years compound yields '
             . 'about 6.34x the base against about 2.92x simple. PENDING A WRITTEN ETPB RULING.',
             'Scheme 1977, Clause 11(ii)', true],

            ['reassessment_cycle_years', '6', 'INT', 'assessment',
             'Periodical re-assessment cycle (years)',
             'Re-assessment after every six years.',
             'Scheme 1977, Clause 11(i)', false],

            ['objection_window_days', '15', 'INT', 'due_process',
             'Objection window (days)',
             'Fifteen days from receipt of notice to file objections.',
             'Scheme 1977, Clause 10(i)(c)', false],

            ['assessment_sla_days', '60', 'INT', 'due_process',
             'Assessment completion SLA (days)',
             'The entire process is completed within 60 days of the first notice, extendable '
             . 'by the Chairman on merit.',
             'Scheme 1977, Clause 10(i)(e)', false],

            ['admin_approval_sla_days', '30', 'INT', 'approval',
             'Administrator approval SLA (days)',
             'Regularization is approved by the Administrator within one month after recording reasons.',
             'Scheme 1977, Clause 3(ii)(d)', false],

            ['processing_fee', '5000.00', 'DECIMAL', 'fee',
             'Application processing fee (Rs.)', $feeNote,
             'Board requirement (not in the 1977 Scheme text)', true],

            ['max_instalments', '24', 'INT', 'arrears',
             'Maximum arrears instalments',
             'Reasonable monthly instalments, not exceeding 24 in number.',
             'Scheme 1977, Clause 13', false],

            ['do_penalty_ceiling', '100000.00', 'DECIMAL', 'enforcement',
             'District Officer penalty ceiling (Rs.)',
             'Penalty to the extent of Rs. 100,000 for a rectifiable breach.',
             'Scheme 1977, Clause 22', false],

            ['ejectment_show_cause_days', '7', 'INT', 'enforcement',
             'Ejectment show-cause period (days)',
             'A period of not less than seven days to show cause.',
             'Scheme 1977, Clause 21(a)', false],

            ['ejectment_vacation_days', '60', 'INT', 'enforcement',
             'Maximum vacation period (days)',
             'Not more than sixty days for vacation of the premises.',
             'Scheme 1977, Clause 21(c)', false],

            ['milestone_years', '2000,2004,2008,2012,2016,2020,2024', 'STRING', 'reporting',
             'Rent table milestone years',
             'Presentation grid for the rent assessment table. This is a view over the '
             . 'year-by-year ledger; the 6-year statutory cycle still governs the law.',
             'Requirements specification', true],

            ['default_unit_profile', 'REVENUE', 'STRING', 'measurement',
             'Default area conversion profile',
             'REVENUE (1 Marla = 272.25 sqft) or URBAN (1 Marla = 225 sqft).',
             'Pakistani revenue measurement practice', true],

            ['organisation_name', 'Evacuee Trust Property Board', 'STRING', 'general',
             'Organisation name', 'Printed on notices, orders and reports.', null, true],

            ['scheme_name', 'Regularization of Possession', 'STRING', 'general',
             'Scheme name', 'Project / scheme title.', null, true],
        ];

        foreach ($rows as [$key, $value, $type, $group, $label, $desc, $ref, $editable]) {
            DB::table('settings')->insert([
                'key'             => $key,
                'value'           => $value,
                'value_type'      => $type,
                'group'           => $group,
                'label'           => $label,
                'description'     => $desc,
                'legal_reference' => $ref,
                'effective_from'  => '1977-01-01',
                'is_editable'     => $editable,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    private function documentTypes(): void
    {
        // [code, name, name_ur, category, certified, mandatory, waivable, proves_date, order]
        $types = [
            ['JAMABANDI', 'Jamabandi (Record of Rights)', 'جمع بندی', 'REVENUE_RECORD', true, true, true, true, 1],
            ['MUTATION', 'Mutation (Intiqal)', 'انتقال', 'REVENUE_RECORD', true, true, true, true, 2],
            ['KHASRA_GIRDAWARI', 'Khasra Girdawari', 'خسرہ گرداوری', 'REVENUE_RECORD', true, true, true, true, 3],
            ['GEO_TAG', 'GEO Tagging (Geo Coordinates)', 'جیو ٹیگنگ', 'SURVEY', false, true, false, false, 4],
            ['BUILDING_PLAN', 'Approved Building Plan', 'منظور شدہ نقشہ', 'PLAN', true, false, true, false, 5],
            ['LOCATION_PLAN', 'Location Plan / Site Plan', 'محل وقوع کا نقشہ', 'PLAN', false, true, true, false, 6],
            ['SATELLITE_IMAGERY', 'Satellite Imagery', 'سیٹلائٹ تصویر', 'SURVEY', false, false, true, true, 7],
            ['BILL_ELECTRICITY', 'Electricity Bill', 'بجلی کا بل', 'UTILITY', false, true, true, true, 8],
            ['BILL_GAS', 'SNGPL / SSGC Gas Bill', 'گیس کا بل', 'UTILITY', false, false, true, true, 9],
            ['BILL_WASA', 'WASA / Water Bill', 'واسا بل', 'UTILITY', false, false, true, true, 10],
            ['COURT_ORDER', 'Court Order / Judicial Declaration', 'عدالتی حکم', 'JUDICIAL', true, false, true, true, 11],
            ['AFFIDAVIT_POSSESSION', 'Affidavit (date of possession and nominee)', 'حلف نامہ', 'AFFIDAVIT', true, true, false, true, 12],
            ['CNIC_COPY', 'CNIC Copy', 'شناختی کارڈ کی نقل', 'IDENTITY', false, true, false, false, 13],
            ['NOMINATION_FORM', 'Nomination Form (Scheme para 3)', 'نامزدگی فارم', 'STATUTORY', false, true, false, false, 14],
            ['FEE_INSTRUMENT', 'Processing Fee Instrument (Rs. 5,000)', 'فیس انسٹرومنٹ', 'FINANCIAL', false, true, false, false, 15],
            ['OTHER', 'Any Other Supporting Document', 'دیگر دستاویز', 'OTHER', false, false, true, false, 99],
        ];

        foreach ($types as [$code, $name, $ur, $cat, $cert, $mand, $waiv, $proves, $order]) {
            DB::table('document_types')->insert([
                'code'                       => $code,
                'name'                       => $name,
                'name_ur'                    => $ur,
                'category'                   => $cat,
                'is_certified_copy_required' => $cert,
                'is_mandatory'               => $mand,
                'is_waivable'                => $waiv,
                'proves_possession_date'     => $proves,
                'display_order'              => $order,
                'is_active'                  => true,
                'created_at'                 => now(),
                'updated_at'                 => now(),
            ]);
        }
    }

    private function rateSources(): void
    {
        $sources = [
            ['FBR', 'FBR Notified Valuation Rate',
             'Rate notified by the Federal Board of Revenue.', false, true, false, 1],
            ['DC_RATE', 'DC (District Collector) Rate',
             'Valuation table notified by the District Collector.', false, true, false, 2],
            ['NESPAK', 'NESPAK Rate',
             'Rate assessed by NESPAK.', false, true, false, 3],
            ['VALUATOR', 'Registered Property Valuator',
             'Rate assessed by a registered or approved property valuator.', false, true, false, 4],
            ['MARKET_ADJOINING', 'Prevailing Market Rent (Adjoining Properties)',
             'Prevalent rent of private or other buildings in the vicinity in similar circumstances '
             . '- the test named in Clause 10(i)(a) and defined in Clause 2(i)(l).',
             false, false, false, 5],
            ['DO_DETERMINED', 'Rate Determined by District Officer',
             'The operative figure. Requires written reasons under Clause 10(i)(d).',
             true, false, true, 6],
        ];

        foreach ($sources as [$code, $name, $desc, $operative, $needsRef, $needsReasons, $order]) {
            DB::table('rate_sources')->insert([
                'code'                  => $code,
                'name'                  => $name,
                'description'           => $desc,
                'is_operative'          => $operative,
                'requires_reference_no' => $needsRef,
                'requires_reasons'      => $needsReasons,
                'display_order'         => $order,
                'is_active'             => true,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
        }
    }
}
