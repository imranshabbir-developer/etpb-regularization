<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Competing occupants and litigation.
 *
 * The requirements spec asks for "rent offered by the illegal occupants" in
 * tabular form, and for whether the matter is pending before any court of law,
 * whether any restraining order exists, and whether it is a direction case.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occupant_offers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->string('occupant_name', 150);
            $t->string('occupant_parentage', 150)->nullable();
            $t->string('occupant_cnic', 13)->nullable();
            $t->string('occupant_contact', 20)->nullable();
            $t->text('occupant_address')->nullable();
            $t->string('portion_occupied', 200)->nullable();
            $t->decimal('area_sqft', 18, 4)->nullable();
            $t->decimal('rent_offered', 15, 2);
            $t->date('offer_date');
            $t->text('terms_offered')->nullable();
            $t->date('possession_since')->nullable();
            $t->enum('status', ['RECORDED', 'UNDER_CONSIDERATION', 'ACCEPTED', 'REJECTED', 'WITHDRAWN'])
              ->default('RECORDED')->index();
            $t->text('remarks')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('litigations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->nullable()->constrained('applications')->cascadeOnDelete();
            $t->foreignId('property_id')->nullable()->constrained('properties')->cascadeOnDelete();
            $t->string('court_name', 200);
            $t->string('case_no', 80);
            $t->string('case_title', 255)->nullable();
            $t->enum('case_type', ['CIVIL_SUIT', 'WRIT_PETITION', 'APPEAL', 'REVISION',
                                   'EXECUTION', 'CONTEMPT', 'DIRECTION_CASE', 'OTHER'])
              ->default('CIVIL_SUIT');
            $t->date('filed_on')->nullable();
            $t->string('petitioner', 255)->nullable();
            $t->string('respondent', 255)->nullable();

            $t->boolean('is_pending')->default(true)->index();
            $t->boolean('has_restraining_order')->default(false)->index();
            $t->date('restraining_order_date')->nullable();
            $t->text('restraining_order_text')->nullable();
            $t->boolean('is_direction_case')->default(false);
            $t->text('direction_summary')->nullable();

            $t->date('next_hearing_date')->nullable();
            $t->text('last_order_summary')->nullable();
            $t->date('last_order_date')->nullable();
            $t->date('disposal_date')->nullable();
            $t->enum('outcome', ['ALLOWED', 'DISMISSED', 'WITHDRAWN', 'COMPROMISED',
                                 'REMANDED', 'ABATED', 'PENDING'])->default('PENDING');
            $t->text('outcome_detail')->nullable();
            $t->string('counsel_name', 150)->nullable();
            $t->text('remarks')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['case_no', 'court_name'], 'idx_litigation_case');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('litigations');
        Schema::dropIfExists('occupant_offers');
    }
};
