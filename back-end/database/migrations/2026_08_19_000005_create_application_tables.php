<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The application itself, its possession particulars and its status history.
 *
 * Eligibility rests on Clause 3(ii)(a): actual physical possession prior to
 * 01-01-2010. The arrears start date is the earliest of 01-07-2000, the date
 * of occupation, and the date of a judicial verdict — Clause 3(ii)(b).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $t) {
            $t->id();
            $t->string('application_no', 60)->unique();   // ETPB/{DISTRICT}/ROP/{YYYY}/{SEQ}
            $t->foreignId('applicant_id')->constrained('applicants')->restrictOnDelete();
            $t->foreignId('property_id')->constrained('properties')->restrictOnDelete();
            $t->foreignId('district_id')->constrained('districts')->restrictOnDelete();
            $t->foreignId('office_id')->nullable()->constrained('offices')->nullOnDelete();
            $t->foreignId('unit_profile_id')->constrained('unit_conversion_profiles')->restrictOnDelete();

            $t->string('status', 40)->default('DRAFT')->index();
            $t->string('previous_status', 40)->nullable();
            $t->text('status_remarks')->nullable();

            $t->unsignedBigInteger('assigned_do_id')->nullable()->index();
            $t->unsignedBigInteger('assigned_admin_id')->nullable()->index();

            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('scrutiny_started_at')->nullable();

            // SLA clocks — Clause 10(i)(e) 60 days, Clause 3(ii)(d) one month.
            $t->date('first_notice_date')->nullable();
            $t->date('assessment_due_date')->nullable();
            $t->date('assessment_extended_to')->nullable();
            $t->string('extension_order_ref', 100)->nullable();
            $t->date('admin_approval_due_date')->nullable();

            $t->timestamp('rent_fixed_at')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('regularized_at')->nullable();
            $t->timestamp('rejected_at')->nullable();
            $t->text('rejection_reason')->nullable();

            $t->boolean('is_sub_judice')->default(false)->index();
            $t->boolean('is_locked')->default(false);

            $t->decimal('assessed_monthly_rent', 15, 2)->nullable();
            $t->decimal('total_arrears', 15, 2)->default(0);
            $t->decimal('arrears_paid', 15, 2)->default(0);
            $t->decimal('arrears_balance', 15, 2)->default(0);

            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['district_id', 'status'], 'idx_app_district_status');
        });

        Schema::create('possession_details', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();

            $t->date('date_of_possession');                  // Clause 3(ii)(a)
            $t->enum('possession_nature', ['SELF', 'INHERITED', 'PURCHASED', 'ALLOTTED', 'OTHER'])
              ->default('SELF');
            $t->text('possession_description')->nullable();
            $t->date('date_of_judicial_verdict')->nullable(); // Clause 3(ii)(b)
            $t->string('judicial_reference', 150)->nullable();

            // Computed: MIN(2000-07-01, occupation, judicial verdict)
            $t->date('arrears_from');
            $t->enum('arrears_from_basis', ['STATUTORY_2000', 'DATE_OF_OCCUPATION', 'JUDICIAL_VERDICT'])
              ->default('STATUTORY_2000');

            $t->boolean('is_eligible')->default(false);
            $t->text('eligibility_reason')->nullable();
            $t->date('cutoff_applied');                       // which cut-off governed
            $t->boolean('is_pre_independence_plot')->default(false);  // Clause 4
            $t->boolean('is_colony_cluster')->default(false);          // Clause 4

            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('application_status_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->string('from_status', 40)->nullable();
            $t->string('to_status', 40);
            $t->string('action', 60)->nullable();
            $t->text('remarks')->nullable();
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->string('actor_role', 40)->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamp('occurred_at')->useCurrent();
            $t->index(['application_id', 'occurred_at'], 'idx_status_hist_app_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_history');
        Schema::dropIfExists('possession_details');
        Schema::dropIfExists('applications');
    }
};
