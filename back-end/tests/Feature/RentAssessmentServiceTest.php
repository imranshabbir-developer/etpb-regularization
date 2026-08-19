<?php

namespace Tests\Feature;

use App\Services\RentAssessmentService;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * The 8% per annum enhancement of Clause 11(ii).
 *
 * These tests pin the arithmetic in both readings of "eight per cent per
 * annum", because the Scheme does not say which one applies and the choice
 * changes every arrears figure the Board will ever issue.
 */
class RentAssessmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private RentAssessmentService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(ReferenceDataSeeder::class);
        $this->svc = app(RentAssessmentService::class);
    }

    public function test_anchor_year_returns_the_determined_rent_unchanged(): void
    {
        $this->assertSame('1000.00', $this->svc->rentForYear('1000', 2006, 2006));
    }

    public function test_compound_enhancement_forward(): void
    {
        $this->assertSame('1080.00', $this->svc->rentForYear('1000', 2006, 2007, '8.00', 'COMPOUND'));
        $this->assertSame('1166.40', $this->svc->rentForYear('1000', 2006, 2008, '8.00', 'COMPOUND'));
        // 1.08^6 = 1.586874322944
        $this->assertSame('1586.87', $this->svc->rentForYear('1000', 2006, 2012, '8.00', 'COMPOUND'));
        // 1.08^18 = 3.996019499184
        $this->assertSame('3996.02', $this->svc->rentForYear('1000', 2006, 2024, '8.00', 'COMPOUND'));
    }

    public function test_simple_enhancement_forward(): void
    {
        $this->assertSame('1080.00', $this->svc->rentForYear('1000', 2006, 2007, '8.00', 'SIMPLE'));
        $this->assertSame('1160.00', $this->svc->rentForYear('1000', 2006, 2008, '8.00', 'SIMPLE'));
        // 1 + 0.08 x 18 = 2.44
        $this->assertSame('2440.00', $this->svc->rentForYear('1000', 2006, 2024, '8.00', 'SIMPLE'));
    }

    /**
     * The gap that MASTER_PLAN risk R1 is about. Over 24 years compound yields
     * about 6.34x the base and simple about 2.92x — a difference of well over
     * double on every arrears demand.
     */
    public function test_compound_and_simple_diverge_materially_over_24_years(): void
    {
        $compound = $this->svc->rentForYear('1000', 2000, 2024, '8.00', 'COMPOUND');
        $simple   = $this->svc->rentForYear('1000', 2000, 2024, '8.00', 'SIMPLE');

        $this->assertSame('6341.18', $compound);   // 1.08^24 = 6.34118073724
        $this->assertSame('2920.00', $simple);     // 1 + 0.08 x 24 = 2.92

        $this->assertGreaterThan(2.0, (float) $compound / (float) $simple);
    }

    public function test_back_casting_before_the_anchor_year(): void
    {
        // Arrears reach back to 2000 while the assessment is anchored in 2006,
        // so earlier years are divided by the same growth factor.
        $this->assertSame('925.93', $this->svc->rentForYear('1000', 2006, 2005, '8.00', 'COMPOUND'));
        $this->assertSame('630.17', $this->svc->rentForYear('1000', 2006, 2000, '8.00', 'COMPOUND'));

        $this->assertSame('675.68', $this->svc->rentForYear('1000', 2006, 2000, '8.00', 'SIMPLE'));
    }

    public function test_back_cast_then_forward_returns_to_the_anchor(): void
    {
        $atYear2000 = $this->svc->rentForYear('1000', 2006, 2000, '8.00', 'COMPOUND');
        $backTo2006 = $this->svc->rentForYear($atYear2000, 2000, 2006, '8.00', 'COMPOUND');

        // Within one rupee — the round trip passes through two roundings.
        $this->assertLessThan(1.0, abs(1000 - (float) $backTo2006));
    }

    public function test_rounding_is_half_up_not_truncated(): void
    {
        // 1000 x 1.08^6 = 1586.874322944 -> 1586.87 (rounds down)
        $this->assertSame('1586.87', $this->svc->rentForYear('1000', 2000, 2006, '8.00', 'COMPOUND'));
        // 1000 x 1.08^18 = 3996.019499184 -> 3996.02 (rounds up, truncation would give 3996.01)
        $this->assertSame('3996.02', $this->svc->rentForYear('1000', 2000, 2018, '8.00', 'COMPOUND'));
    }

    public function test_zero_rate_leaves_rent_flat(): void
    {
        $this->assertSame('1500.00', $this->svc->rentForYear('1500', 2000, 2024, '0.00', 'COMPOUND'));
    }

    public function test_unknown_method_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc->rentForYear('1000', 2006, 2010, '8.00', 'GEOMETRIC');
    }

    public function test_rent_year_opens_on_first_july(): void
    {
        // Both statutory anchors fall on 1 July, so the rent year does too.
        $this->assertSame(2006, $this->svc->rentYearOf(Carbon::parse('2006-07-01')));
        $this->assertSame(2006, $this->svc->rentYearOf(Carbon::parse('2007-06-30')));
        $this->assertSame(2007, $this->svc->rentYearOf(Carbon::parse('2007-07-01')));
        $this->assertSame(1999, $this->svc->rentYearOf(Carbon::parse('2000-06-30')));
        $this->assertSame(2000, $this->svc->rentYearOf(Carbon::parse('2000-07-01')));
    }

    public function test_larger_base_rent_scales_linearly_up_to_rounding(): void
    {
        // 1000 x 1.08^10 = 2158.924997 -> 2158.92, and x10 = 21589.20
        // 10000 x 1.08^10 = 21589.24997 -> 21589.25
        // Scaling is linear in the exact arithmetic; the 5 paisa gap is the
        // single rounding applied at the end of each computation.
        $one = $this->svc->rentForYear('1000', 2006, 2016, '8.00', 'COMPOUND');
        $ten = $this->svc->rentForYear('10000', 2006, 2016, '8.00', 'COMPOUND');

        $this->assertSame('2158.92', $one);
        $this->assertSame('21589.25', $ten);
        $this->assertLessThanOrEqual(0.10, abs((float) $one * 10 - (float) $ten));
    }
}
