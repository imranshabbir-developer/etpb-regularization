<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Approval chain and outcome.
 *
 * Clause 3(ii)(d): "the regularization shall be approved by the Administrator
 * within one month after recording reasons" — hence reasons are NOT NULL.
 *
 * Scheme para 3(iii)(B) proviso: "the District Officer shall not transfer the
 * tenancy or regularize the possession unless he has obtained the aforesaid
 * nominee form" — hence the nominee record gates agreement execution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->enum('level', ['DISTRICT_OFFICER', 'ADMINISTRATOR', 'CHAIRMAN']);
            $t->enum('action', ['APPROVE', 'REJECT', 'RETURN', 'DEFER', 'REMAND']);
            // Mandatory — the Scheme requires recorded reasons.
            $t->text('reasons');
            $t->text('conditions')->nullable();
            $t->unsignedBigInteger('acted_by');
            $t->timestamp('acted_at');
            $t->date('due_by')->nullable();
            $t->boolean('is_within_sla')->default(true);
            $t->unsignedSmallInteger('days_taken')->nullable();
            $t->string('order_reference', 120)->nullable();
            $t->string('order_path', 255)->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['application_id', 'level'], 'idx_approval_app_level');
        });

        Schema::create('nominees', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->string('nominee_name', 150);
            $t->string('nominee_parentage', 150)->nullable();
            $t->string('relationship', 80);
            $t->string('nominee_cnic', 13)->nullable();
            $t->string('nominee_contact', 20)->nullable();
            $t->text('nominee_address')->nullable();
            $t->decimal('share_percentage', 5, 2)->nullable();
            $t->date('form_received_on');
            $t->string('form_path', 255)->nullable();
            $t->boolean('is_verified')->default(false);
            $t->unsignedBigInteger('verified_by')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('nominee_heirs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('nominee_id')->constrained('nominees')->cascadeOnDelete();
            $t->string('heir_name', 150);
            $t->string('relationship', 80);
            $t->string('cnic', 13)->nullable();
            $t->date('date_of_birth')->nullable();
            $t->unsignedSmallInteger('display_order')->default(0);
            $t->timestamps();
        });

        Schema::create('tenancy_agreements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->string('agreement_no', 80)->unique();
            $t->date('executed_on');
            $t->unsignedBigInteger('executed_by');          // the District Officer
            $t->foreignId('applicant_id')->constrained('applicants')->restrictOnDelete();
            $t->decimal('monthly_rent', 15, 2);
            $t->decimal('security_amount', 15, 2)->nullable();  // Scheme 2(i)(p)
            $t->date('effective_from');
            $t->date('valid_till')->nullable();
            $t->longText('terms')->nullable();
            $t->string('stamp_paper_no', 80)->nullable();
            $t->decimal('stamp_paper_value', 15, 2)->nullable();
            $t->date('stamp_paper_date')->nullable();
            $t->string('signed_scan_path', 255)->nullable();
            $t->enum('status', ['DRAFT', 'EXECUTED', 'ACTIVE', 'TERMINATED', 'CANCELLED'])
              ->default('DRAFT')->index();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('regularization_orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->string('order_no', 80)->unique();
            $t->date('order_date');
            $t->unsignedBigInteger('issued_by');
            $t->string('issued_by_designation', 120)->nullable();
            $t->longText('order_text');
            $t->decimal('regularized_area_sqft', 18, 4)->nullable();
            $t->decimal('monthly_rent_fixed', 15, 2)->nullable();
            $t->string('pdf_path', 255)->nullable();
            $t->enum('status', ['DRAFT', 'ISSUED', 'CANCELLED'])->default('DRAFT')->index();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regularization_orders');
        Schema::dropIfExists('tenancy_agreements');
        Schema::dropIfExists('nominee_heirs');
        Schema::dropIfExists('nominees');
        Schema::dropIfExists('approvals');
    }
};
