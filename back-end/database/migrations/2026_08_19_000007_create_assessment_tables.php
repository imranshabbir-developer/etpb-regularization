<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rent assessment under Clause 10, and the year-wise schedule that carries the
 * 8% per annum enhancement of Clause 11(ii).
 *
 * Each evidence-of-value input is stored as its own row so the District
 * Officer's determination is defensible on the record: FBR rate, DC rate,
 * NESPAK/valuator rate, prevailing market rent of adjoining properties, and
 * finally the rate the DO determines with written reasons.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_rounds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->foreignId('property_id')->constrained('properties')->restrictOnDelete();
            $t->unsignedSmallInteger('round_no')->default(1);
            $t->enum('round_type', ['INITIAL', 'PERIODICAL', 'REVISION'])->default('INITIAL');

            $t->date('base_date');            // 01-07-2006 per Clause 10(i)
            $t->date('effective_from');
            $t->decimal('enhancement_rate', 5, 2)->default(8.00);          // Clause 11(ii)
            $t->enum('enhancement_method', ['SIMPLE', 'COMPOUND'])->default('COMPOUND');
            $t->unsignedSmallInteger('reassessment_cycle_years')->default(6); // Clause 11(i)

            $t->string('status', 40)->default('DRAFT')->index();
            $t->unsignedBigInteger('district_officer_id')->nullable();

            // Clause 10(i)(e): 60 days from first notice, extendable by Chairman.
            $t->date('first_notice_date')->nullable();
            $t->date('completion_due_date')->nullable();
            $t->date('extended_to')->nullable();
            $t->string('extension_reference', 120)->nullable();
            $t->unsignedBigInteger('extension_approved_by')->nullable();

            $t->decimal('proposed_monthly_rent', 15, 2)->nullable();
            $t->decimal('determined_monthly_rent', 15, 2)->nullable();
            $t->timestamp('completed_at')->nullable();

            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['application_id', 'round_no'], 'uq_app_round');
        });

        Schema::create('assessment_rate_inputs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assessment_round_id')->constrained('assessment_rounds')->cascadeOnDelete();
            $t->foreignId('rate_source_id')->constrained('rate_sources')->restrictOnDelete();
            $t->decimal('rate_value', 15, 2);
            $t->enum('rate_unit', ['PER_SQFT_PER_MONTH', 'PER_MARLA_PER_MONTH', 'PER_MONTH_TOTAL',
                                   'PER_SQFT_VALUE', 'PER_MARLA_VALUE', 'TOTAL_VALUE'])
              ->default('PER_SQFT_PER_MONTH');
            $t->string('notification_no', 120)->nullable();
            $t->date('notification_date')->nullable();
            $t->date('effective_from')->nullable();
            $t->date('effective_to')->nullable();
            $t->string('valuator_name', 150)->nullable();
            $t->string('valuator_licence_no', 80)->nullable();
            $t->string('report_no', 120)->nullable();
            $t->date('report_date')->nullable();
            $t->text('remarks')->nullable();
            $t->string('attachment_path', 255)->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('assessment_comparables', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assessment_round_id')->constrained('assessment_rounds')->cascadeOnDelete();
            $t->string('property_description', 255);
            $t->string('address', 255)->nullable();
            $t->decimal('area_sqft', 18, 4)->nullable();
            $t->decimal('monthly_rent', 15, 2);
            $t->enum('usage_type', ['RESIDENTIAL', 'COMMERCIAL', 'RESIDENTIAL_CUM_COMMERCIAL', 'OTHER'])
              ->default('RESIDENTIAL');
            $t->decimal('distance_meters', 10, 2)->nullable();
            $t->string('information_source', 200)->nullable();
            $t->date('observed_on')->nullable();
            $t->text('remarks')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('assessment_decisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assessment_round_id')->constrained('assessment_rounds')->cascadeOnDelete();
            $t->decimal('determined_monthly_rent', 15, 2);
            $t->decimal('rate_per_sqft', 15, 4)->nullable();
            // Mandatory: Clause 10(i)(d) requires a reasoned fixation after hearing.
            $t->text('reasons');
            $t->text('objections_considered')->nullable();
            $t->unsignedBigInteger('decided_by');
            $t->timestamp('decided_at');
            $t->boolean('is_superseded')->default(false);
            $t->unsignedBigInteger('superseded_by_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('rent_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assessment_round_id')->constrained('assessment_rounds')->cascadeOnDelete();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->unsignedSmallInteger('year');
            $t->date('period_from');
            $t->date('period_to');
            $t->decimal('monthly_rent', 15, 2);
            $t->decimal('annual_rent', 15, 2);
            $t->decimal('enhancement_applied_pct', 8, 4)->default(0);
            $t->unsignedSmallInteger('years_elapsed')->default(0);
            $t->boolean('is_reassessment_year')->default(false);
            $t->boolean('is_milestone_year')->default(false);   // the 2000/2004/... report grid
            $t->text('computation_note')->nullable();
            $t->timestamps();
            $t->unique(['assessment_round_id', 'year'], 'uq_round_year');
            $t->index(['application_id', 'year'], 'idx_sched_app_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_schedules');
        Schema::dropIfExists('assessment_decisions');
        Schema::dropIfExists('assessment_comparables');
        Schema::dropIfExists('assessment_rate_inputs');
        Schema::dropIfExists('assessment_rounds');
    }
};
