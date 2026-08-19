<?php

namespace Tests\Feature;

use App\Services\ArrearsService;
use App\Services\EligibilityService;
use App\Services\RentAssessmentService;
use Database\Seeders\GeographySeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Generation of the persisted schedule and the ledger drawn from it.
 *
 * The two assertions that matter most here were both live defects found by
 * driving a real case through the application:
 *
 *   - a complete rent year measured 11.9672 months rather than 12, because
 *     Carbon returns a float from diffInDays and the year was being measured
 *     against an end-of-day boundary. Every full year of every assessment was
 *     being under-charged by roughly 0.27%.
 *
 *   - the closing period ran to the following 30 June, charging rent for
 *     months that had not yet fallen due and calling it arrears.
 */
class RentScheduleGenerationTest extends TestCase
{
    use RefreshDatabase;

    private int $applicationId;
    private int $roundId;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(ReferenceDataSeeder::class);
        $this->seed(GeographySeeder::class);
    }

    public function test_a_complete_rent_year_is_charged_twelve_months(): void
    {
        $this->buildCase('1998-04-12', '1000.00');
        app(RentAssessmentService::class)->generateSchedule($this->roundId);

        // 1999 is a rent year wholly inside the arrears period and wholly past.
        $row = DB::table('rent_schedules')
            ->where('application_id', $this->applicationId)
            ->where('year', 1999)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('1999-07-01', $row->period_from);
        $this->assertSame('2000-06-30', $row->period_to);

        // annual = monthly x 12, exactly.
        $expected = bcmul((string) $row->monthly_rent, '12', 2);
        $this->assertSame(
            round((float) $expected, 2),
            round((float) $row->annual_rent, 2),
            'A complete rent year must be charged exactly twelve months.'
        );
    }

    public function test_the_opening_year_is_proportioned_to_the_arrears_start(): void
    {
        // Possession on 12-04-1998 falls inside rent year 1997.
        $this->buildCase('1998-04-12', '1000.00');
        app(RentAssessmentService::class)->generateSchedule($this->roundId);

        $row = DB::table('rent_schedules')
            ->where('application_id', $this->applicationId)
            ->orderBy('year')
            ->first();

        $this->assertSame(1997, (int) $row->year);
        $this->assertSame('1998-04-12', $row->period_from);
        $this->assertSame('1998-06-30', $row->period_to);

        // 80 days of a 365-day year is well under a full year's rent.
        $months = bcdiv((string) $row->annual_rent, (string) $row->monthly_rent, 4);
        $this->assertLessThan(3.0, (float) $months);
        $this->assertGreaterThan(2.5, (float) $months);
    }

    public function test_the_closing_period_stops_at_today_not_the_following_june(): void
    {
        $this->buildCase('1998-04-12', '1000.00');
        app(RentAssessmentService::class)->generateSchedule($this->roundId);

        $last = DB::table('rent_schedules')
            ->where('application_id', $this->applicationId)
            ->orderByDesc('year')
            ->first();

        $this->assertSame(
            Carbon::today()->toDateString(),
            $last->period_to,
            'Rent for months that have not fallen due is not arrears.'
        );

        // The current year must therefore be charged less than a full 12 months.
        $months = bcdiv((string) $last->annual_rent, (string) $last->monthly_rent, 4);
        $this->assertLessThanOrEqual(12.0, (float) $months);
    }

    public function test_the_schedule_spans_arrears_start_to_the_current_rent_year(): void
    {
        $this->buildCase('1998-04-12', '1000.00');
        app(RentAssessmentService::class)->generateSchedule($this->roundId);

        $svc = app(RentAssessmentService::class);
        $expectedFirst = $svc->rentYearOf(Carbon::parse('1998-04-12'));
        $expectedLast  = $svc->rentYearOf(Carbon::today());

        $years = DB::table('rent_schedules')
            ->where('application_id', $this->applicationId)
            ->orderBy('year')
            ->pluck('year');

        $this->assertSame($expectedFirst, (int) $years->first());
        $this->assertSame($expectedLast, (int) $years->last());
        $this->assertCount($expectedLast - $expectedFirst + 1, $years);
    }

    public function test_milestone_years_are_flagged(): void
    {
        $this->buildCase('1998-04-12', '1000.00');
        app(RentAssessmentService::class)->generateSchedule($this->roundId);

        $flagged = DB::table('rent_schedules')
            ->where('application_id', $this->applicationId)
            ->where('is_milestone_year', true)
            ->pluck('year')
            ->map(fn ($y) => (int) $y)
            ->all();

        // Every milestone year in scope should be flagged.
        foreach ([2000, 2004, 2008, 2012, 2016, 2020, 2024] as $y) {
            $this->assertContains($y, $flagged);
        }
    }

    public function test_reassessment_years_fall_on_the_six_year_cycle(): void
    {
        $this->buildCase('1998-04-12', '1000.00');
        app(RentAssessmentService::class)->generateSchedule($this->roundId);

        $rows = DB::table('rent_schedules')
            ->where('application_id', $this->applicationId)
            ->where('is_reassessment_year', true)
            ->pluck('year');

        // Anchor is rent year 2006, so every flagged year is 2006 ± a multiple of 6.
        foreach ($rows as $y) {
            $this->assertSame(0, abs((int) $y - 2006) % 6, "Year {$y} is not on the six-year cycle.");
        }
    }

    public function test_the_ledger_mirrors_the_schedule_and_rolls_up_to_the_application(): void
    {
        $this->buildCase('1998-04-12', '1000.00');
        app(RentAssessmentService::class)->generateSchedule($this->roundId);
        $result = app(ArrearsService::class)->generate($this->applicationId);

        $scheduleTotal = DB::table('rent_schedules')
            ->where('application_id', $this->applicationId)->sum('annual_rent');
        $ledgerTotal = DB::table('arrears_ledger')
            ->where('application_id', $this->applicationId)->sum('amount_due');

        $this->assertSame(round((float) $scheduleTotal, 2), round((float) $ledgerTotal, 2));
        $this->assertSame(
            DB::table('rent_schedules')->where('application_id', $this->applicationId)->count(),
            $result['rows']
        );

        $app = DB::table('applications')->find($this->applicationId);
        $this->assertSame(round((float) $ledgerTotal, 2), round((float) $app->total_arrears, 2));
        $this->assertSame(round((float) $ledgerTotal, 2), round((float) $app->arrears_balance, 2));
    }

    public function test_a_receipt_clears_the_oldest_years_first(): void
    {
        $this->buildCase('1998-04-12', '1000.00');
        app(RentAssessmentService::class)->generateSchedule($this->roundId);
        app(ArrearsService::class)->generate($this->applicationId);

        $first = DB::table('arrears_ledger')
            ->where('application_id', $this->applicationId)->orderBy('period_year')->first();

        app(ArrearsService::class)->postReceipt(
            $this->applicationId,
            (string) $first->amount_due,
            Carbon::today()->toDateString(),
            'CASH',
        );

        $first->refresh ?? null;
        $reloaded = DB::table('arrears_ledger')->find($first->id);

        $this->assertSame(0.0, round((float) $reloaded->balance, 2));
        $this->assertSame(round((float) $first->amount_due, 2), round((float) $reloaded->amount_paid, 2));
    }

    // ---- fixture ---------------------------------------------------------

    private function buildCase(string $possessionDate, string $rent): void
    {
        $districtId = DB::table('districts')->where('name', 'Lahore')->value('id');
        $provinceId = DB::table('districts')->where('id', $districtId)->value('province_id');
        $profileId  = DB::table('unit_conversion_profiles')->where('code', 'REVENUE')->value('id');

        $applicantId = DB::table('applicants')->insertGetId([
            'full_name' => 'Ram Lal', 'parentage_type' => 'FATHER', 'parentage_name' => 'Diwan Chand',
            'cnic' => '3520112349876', 'contact' => '0300-4455667',
            'postal_address' => 'House 14-B, Krishan Nagar, Lahore',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $propertyId = DB::table('properties')->insertGetId([
            'property_no' => 'KN-14-B', 'property_type' => 'HOUSE', 'usage_type' => 'RESIDENTIAL',
            'address' => '14-B Krishan Nagar, Lahore',
            'province_id' => $provinceId, 'district_id' => $districtId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->applicationId = DB::table('applications')->insertGetId([
            'application_no' => 'ETPB/TEST/ROP/2026/' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'applicant_id' => $applicantId, 'property_id' => $propertyId,
            'district_id' => $districtId, 'unit_profile_id' => $profileId,
            'status' => 'RENT_FIXED', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $assessment = app(EligibilityService::class)->assess($possessionDate);

        DB::table('possession_details')->insert([
            'application_id' => $this->applicationId,
            'date_of_possession' => $possessionDate, 'possession_nature' => 'INHERITED',
            'arrears_from' => $assessment['arrears_from'],
            'arrears_from_basis' => $assessment['arrears_from_basis'],
            'is_eligible' => $assessment['is_eligible'],
            'eligibility_reason' => $assessment['reason'],
            'cutoff_applied' => $assessment['cutoff_applied'],
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->roundId = DB::table('assessment_rounds')->insertGetId([
            'application_id' => $this->applicationId, 'property_id' => $propertyId,
            'round_no' => 1, 'round_type' => 'INITIAL',
            'base_date' => '2006-07-01', 'effective_from' => '2006-07-01',
            'enhancement_rate' => '8.00', 'enhancement_method' => 'COMPOUND',
            'reassessment_cycle_years' => 6, 'status' => 'DECIDED',
            'determined_monthly_rent' => $rent,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
