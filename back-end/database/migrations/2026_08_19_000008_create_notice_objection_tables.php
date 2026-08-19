<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Due process under Clause 10(i)(b)-(d): the proposed assessment is made
 * publicly available, notice is given to the tenant and the general public,
 * 15 days are allowed for objections, and the rent is fixed only after an
 * opportunity of hearing to the tenant and any objector.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_notices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->foreignId('assessment_round_id')->nullable()->constrained('assessment_rounds')->cascadeOnDelete();
            $t->string('notice_no', 80)->unique();
            $t->enum('notice_type', ['PUBLIC', 'TENANT', 'OBJECTOR', 'SHOW_CAUSE', 'HEARING'])
              ->default('PUBLIC');
            $t->date('issued_on');
            $t->date('served_on')->nullable();
            $t->enum('service_mode', ['HAND', 'REGISTERED_POST', 'COURIER', 'NEWSPAPER',
                                      'NOTICE_BOARD', 'AFFIXATION', 'EMAIL', 'SMS'])
              ->default('HAND');
            $t->string('newspaper_name', 150)->nullable();
            $t->date('published_on')->nullable();
            $t->string('publication_reference', 150)->nullable();
            // Clause 10(i)(c): 15 days from receipt of notice.
            $t->date('objection_deadline');
            $t->text('subject')->nullable();
            $t->longText('body')->nullable();
            $t->string('document_path', 255)->nullable();
            $t->enum('status', ['DRAFT', 'ISSUED', 'SERVED', 'PUBLISHED', 'EXPIRED'])
              ->default('DRAFT')->index();
            $t->unsignedBigInteger('issued_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('objections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->foreignId('public_notice_id')->nullable()->constrained('public_notices')->nullOnDelete();
            $t->string('objection_no', 80)->unique();
            // Particulars of objectors, per the requirements spec.
            $t->string('objector_name', 150);
            $t->string('objector_parentage', 150)->nullable();
            $t->string('objector_cnic', 13)->nullable();
            $t->text('objector_address')->nullable();
            $t->string('objector_contact', 20)->nullable();
            $t->string('relationship_to_property', 150)->nullable();
            $t->longText('plea');                              // full text, verbatim
            $t->date('filed_on');
            $t->boolean('is_within_time')->default(true);
            $t->text('late_filing_reason')->nullable();
            $t->enum('status', ['FILED', 'UNDER_HEARING', 'DECIDED', 'WITHDRAWN'])
              ->default('FILED')->index();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('hearings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->foreignId('assessment_round_id')->nullable()->constrained('assessment_rounds')->nullOnDelete();
            $t->string('hearing_no', 80)->nullable();
            $t->dateTime('scheduled_for');
            $t->string('venue', 200)->nullable();
            $t->unsignedBigInteger('presiding_officer_id')->nullable();
            $t->string('presiding_designation', 120)->nullable();
            $t->json('parties_summoned')->nullable();
            $t->json('attendance')->nullable();
            $t->longText('proceedings')->nullable();
            $t->date('adjourned_to')->nullable();
            $t->text('adjournment_reason')->nullable();
            $t->enum('status', ['SCHEDULED', 'HELD', 'ADJOURNED', 'CANCELLED'])
              ->default('SCHEDULED')->index();
            $t->string('minutes_path', 255)->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('objection_decisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('objection_id')->constrained('objections')->cascadeOnDelete();
            $t->foreignId('hearing_id')->nullable()->constrained('hearings')->nullOnDelete();
            $t->enum('decision', ['ACCEPTED', 'REJECTED', 'PARTIALLY_ACCEPTED', 'WITHDRAWN']);
            $t->text('reasons');
            $t->decimal('rent_impact', 15, 2)->nullable();
            $t->unsignedBigInteger('decided_by');
            $t->timestamp('decided_at');
            $t->string('order_path', 255)->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objection_decisions');
        Schema::dropIfExists('hearings');
        Schema::dropIfExists('objections');
        Schema::dropIfExists('public_notices');
    }
};
