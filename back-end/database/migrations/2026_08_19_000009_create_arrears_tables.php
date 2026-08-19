<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arrears ledger and recovery.
 *
 * Clause 3(ii)(b) requires the occupant to clear all arrears before being
 * treated as a tenant. Clause 12 lets the Chairman remit rent for the
 * indigent, orphans and widows; Clause 13 lets the District Officer allow
 * recovery in not more than 24 monthly instalments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arrears_ledger', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->foreignId('assessment_round_id')->nullable()->constrained('assessment_rounds')->nullOnDelete();
            $t->unsignedSmallInteger('period_year');
            $t->date('period_from');
            $t->date('period_to');
            $t->decimal('monthly_rent', 15, 2);
            $t->decimal('months_applicable', 8, 4)->default(12);
            $t->decimal('amount_due', 15, 2);
            $t->decimal('amount_paid', 15, 2)->default(0);
            $t->decimal('remission_amount', 15, 2)->default(0);
            $t->decimal('balance', 15, 2);
            $t->text('note')->nullable();
            $t->timestamps();
            $t->unique(['application_id', 'period_year'], 'uq_ledger_app_year');
        });

        Schema::create('payment_receipts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->string('receipt_no', 60)->unique();
            $t->date('receipt_date');
            $t->decimal('amount', 15, 2);
            $t->enum('payment_mode', ['CASH', 'PAY_ORDER', 'BANKERS_CHEQUE', 'DEMAND_DRAFT',
                                      'BANK_TRANSFER', 'CHALLAN'])->default('CASH');
            $t->string('instrument_no', 60)->nullable();
            $t->string('bank_name', 150)->nullable();
            $t->string('branch_code', 30)->nullable();
            $t->enum('applied_to', ['ARREARS', 'CURRENT_RENT', 'PENALTY', 'PROCESSING_FEE', 'OTHER'])
              ->default('ARREARS');
            $t->unsignedSmallInteger('applied_year')->nullable();
            $t->string('scan_path', 255)->nullable();
            $t->text('remarks')->nullable();
            $t->enum('status', ['POSTED', 'BOUNCED', 'CANCELLED'])->default('POSTED')->index();
            $t->unsignedBigInteger('received_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('instalment_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->decimal('total_amount', 15, 2);
            // Clause 13: not exceeding twenty four in number.
            $t->unsignedSmallInteger('instalment_count');
            $t->decimal('instalment_amount', 15, 2);
            $t->date('start_date');
            $t->date('end_date');
            $t->text('justification')->nullable();
            $t->enum('status', ['PROPOSED', 'APPROVED', 'REJECTED', 'COMPLETED', 'DEFAULTED'])
              ->default('PROPOSED')->index();
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->text('approval_reasons')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('instalment_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('instalment_plan_id')->constrained('instalment_plans')->cascadeOnDelete();
            $t->unsignedSmallInteger('instalment_no');
            $t->date('due_date');
            $t->decimal('amount_due', 15, 2);
            $t->decimal('amount_paid', 15, 2)->default(0);
            $t->date('paid_on')->nullable();
            $t->foreignId('receipt_id')->nullable()->constrained('payment_receipts')->nullOnDelete();
            $t->enum('status', ['PENDING', 'PAID', 'PARTIAL', 'OVERDUE'])->default('PENDING')->index();
            $t->timestamps();
            $t->unique(['instalment_plan_id', 'instalment_no'], 'uq_plan_instalment');
        });

        Schema::create('remissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            // Clause 12 grounds.
            $t->enum('ground', ['INDIGENT', 'ORPHAN', 'WIDOW', 'INCAPABLE', 'OTHER']);
            $t->enum('remission_type', ['NOMINAL_RENT', 'REMIT_RENT', 'REMIT_ARREARS', 'PARTIAL']);
            $t->decimal('nominal_monthly_rent', 15, 2)->nullable();
            $t->decimal('remitted_amount', 15, 2)->nullable();
            $t->decimal('remitted_percentage', 5, 2)->nullable();
            $t->text('grounds_detail');
            $t->text('supporting_evidence')->nullable();
            $t->enum('status', ['PROPOSED', 'APPROVED', 'REJECTED'])->default('PROPOSED')->index();
            // Only the Chairman is competent under Clause 12.
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->text('approval_reasons')->nullable();
            $t->string('order_reference', 120)->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remissions');
        Schema::dropIfExists('instalment_schedules');
        Schema::dropIfExists('instalment_plans');
        Schema::dropIfExists('payment_receipts');
        Schema::dropIfExists('arrears_ledger');
    }
};
