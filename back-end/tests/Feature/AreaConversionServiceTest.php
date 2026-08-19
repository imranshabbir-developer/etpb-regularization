<?php

namespace Tests\Feature;

use App\Services\AreaConversionService;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Area conversion is the first thing that had to be right: it is small, purely
 * computational, and every rupee of assessed rent is derived from its output.
 *
 * The reference figures are the Pakistani revenue standard —
 * 1 Acre = 8 Kanal = 160 Marla = 43,560 sqft, 1 Marla = 9 Sarsai.
 */
class AreaConversionServiceTest extends TestCase
{
    use RefreshDatabase;

    private AreaConversionService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(ReferenceDataSeeder::class);
        $this->svc = app(AreaConversionService::class);
    }

    public function test_revenue_profile_base_units(): void
    {
        $this->assertSame('272.2500', $this->svc->toSqft(['MARLA' => 1], 'REVENUE')['sqft']);
        $this->assertSame('5445.0000', $this->svc->toSqft(['KANAL' => 1], 'REVENUE')['sqft']);
        $this->assertSame('43560.0000', $this->svc->toSqft(['ACRE' => 1], 'REVENUE')['sqft']);
        $this->assertSame('30.2500', $this->svc->toSqft(['SARSAI' => 1], 'REVENUE')['sqft']);
        $this->assertSame('9.0000', $this->svc->toSqft(['SQYD' => 1], 'REVENUE')['sqft']);
    }

    public function test_revenue_internal_consistency(): void
    {
        // 20 Marla must equal 1 Kanal.
        $this->assertSame(
            $this->svc->toSqft(['KANAL' => 1], 'REVENUE')['sqft'],
            $this->svc->toSqft(['MARLA' => 20], 'REVENUE')['sqft']
        );

        // 8 Kanal must equal 1 Acre.
        $this->assertSame(
            $this->svc->toSqft(['ACRE' => 1], 'REVENUE')['sqft'],
            $this->svc->toSqft(['KANAL' => 8], 'REVENUE')['sqft']
        );

        // 9 Sarsai must equal 1 Marla.
        $this->assertSame(
            $this->svc->toSqft(['MARLA' => 1], 'REVENUE')['sqft'],
            $this->svc->toSqft(['SARSAI' => 9], 'REVENUE')['sqft']
        );

        // 25 Acre must equal 1 Murabba.
        $this->assertSame(
            $this->svc->toSqft(['MURABBA' => 1], 'REVENUE')['sqft'],
            $this->svc->toSqft(['ACRE' => 25], 'REVENUE')['sqft']
        );
    }

    public function test_compound_entry(): void
    {
        // 2 Kanal 7 Marla 3 Sarsai
        //   = (2 x 5445) + (7 x 272.25) + (3 x 30.25)
        //   = 10890 + 1905.75 + 90.75 = 12886.50
        $result = $this->svc->toSqft(
            ['KANAL' => 2, 'MARLA' => 7, 'SARSAI' => 3],
            'REVENUE'
        );

        $this->assertSame('12886.5000', $result['sqft']);
        $this->assertCount(3, $result['trace']['components']);
        $this->assertSame('REVENUE', $result['trace']['profile_code']);
    }

    public function test_urban_profile_differs_from_revenue(): void
    {
        $this->assertSame('225.0000', $this->svc->toSqft(['MARLA' => 1], 'URBAN')['sqft']);
        $this->assertSame('4500.0000', $this->svc->toSqft(['KANAL' => 1], 'URBAN')['sqft']);

        // The 21% gap that risk R2 is about: 10 Marla is 2,722.50 sqft on the
        // revenue standard but only 2,250.00 sqft in an urban housing scheme.
        $revenue = $this->svc->toSqft(['MARLA' => 10], 'REVENUE')['sqft'];
        $urban   = $this->svc->toSqft(['MARLA' => 10], 'URBAN')['sqft'];

        $this->assertSame('2722.5000', $revenue);
        $this->assertSame('2250.0000', $urban);
        $this->assertNotSame($revenue, $urban);
    }

    public function test_acre_is_identical_across_profiles(): void
    {
        // An Acre is a fixed imperial unit; only the Marla is disputed.
        $this->assertSame(
            $this->svc->toSqft(['ACRE' => 3], 'REVENUE')['sqft'],
            $this->svc->toSqft(['ACRE' => 3], 'URBAN')['sqft']
        );
    }

    public function test_trace_records_the_factors_actually_used(): void
    {
        $trace = $this->svc->toSqft(['MARLA' => 4], 'REVENUE')['trace'];
        $component = $trace['components'][0];

        $this->assertSame('MARLA', $component['unit_code']);
        $this->assertSame('272.2500', $component['sqft_per_unit']);
        $this->assertSame('1089.0000', $component['subtotal_sqft']);
        $this->assertArrayHasKey('computed_at', $trace);
    }

    public function test_round_trip_to_compound(): void
    {
        $sqft = $this->svc->toSqft(['KANAL' => 2, 'MARLA' => 7, 'SARSAI' => 3], 'REVENUE')['sqft'];
        $compound = $this->svc->toCompound($sqft, 'REVENUE');

        $this->assertSame('2 Kanal 7 Marla 3 Sarsai', $compound['label']);
        $this->assertSame('0.0000', $compound['remainder_sqft']);
    }

    public function test_from_sqft(): void
    {
        $this->assertSame('1.0000', $this->svc->fromSqft('272.25', 'MARLA', 'REVENUE'));
        $this->assertSame('2.0000', $this->svc->fromSqft('10890', 'KANAL', 'REVENUE'));
    }

    public function test_decimal_quantities_stay_exact(): void
    {
        // 3.5 Marla = 952.875 -> stored at 4dp
        $this->assertSame('952.8750', $this->svc->toSqft(['MARLA' => '3.5'], 'REVENUE')['sqft']);
    }

    public function test_thousands_separators_are_accepted(): void
    {
        $this->assertSame('12500.0000', $this->svc->toSqft(['SQFT' => '12,500'], 'REVENUE')['sqft']);
    }

    public function test_zero_components_are_ignored_but_all_zero_is_rejected(): void
    {
        $result = $this->svc->toSqft(['KANAL' => 1, 'MARLA' => 0, 'SARSAI' => null], 'REVENUE');
        $this->assertSame('5445.0000', $result['sqft']);
        $this->assertCount(1, $result['trace']['components']);

        $this->expectException(InvalidArgumentException::class);
        $this->svc->toSqft(['KANAL' => 0, 'MARLA' => 0], 'REVENUE');
    }

    public function test_negative_area_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc->toSqft(['MARLA' => -5], 'REVENUE');
    }

    public function test_unit_absent_from_profile_is_rejected(): void
    {
        // The urban profile has no Sarsai.
        $this->expectException(InvalidArgumentException::class);
        $this->svc->toSqft(['SARSAI' => 3], 'URBAN');
    }

    public function test_unknown_profile_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc->toSqft(['MARLA' => 1], 'NO_SUCH_PROFILE');
    }

    public function test_non_numeric_value_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->svc->toSqft(['MARLA' => 'ten'], 'REVENUE');
    }
}
