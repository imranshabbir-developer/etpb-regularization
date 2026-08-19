<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enforcement (Scheme Chapter VII) plus system-level tables.
 *
 * Clause 21 sets the ejectment procedure: a show cause notice of not less than
 * seven days, an order only after an opportunity of being heard, and up to
 * sixty days to vacate. Clause 22 caps the District Officer's penalty at
 * Rs. 100,000 for a rectifiable breach.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalties', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->nullable()->constrained('applications')->cascadeOnDelete();
            $t->foreignId('property_id')->nullable()->constrained('properties')->cascadeOnDelete();
            $t->string('penalty_no', 60)->unique();
            $t->text('breach_description');
            $t->boolean('is_rectifiable')->default(true);
            $t->decimal('amount', 15, 2)->default(0);   // ceiling enforced in the service layer
            $t->date('imposed_on');
            $t->unsignedBigInteger('imposed_by');
            $t->string('show_cause_reference', 120)->nullable();
            $t->date('show_cause_date')->nullable();
            $t->foreignId('hearing_id')->nullable()->constrained('hearings')->nullOnDelete();
            $t->text('order_text')->nullable();
            $t->date('rectification_deadline')->nullable();
            $t->enum('status', ['SHOW_CAUSE_ISSUED', 'IMPOSED', 'PAID', 'WAIVED',
                                'TENANCY_CANCELLED', 'UNDER_APPEAL'])
              ->default('SHOW_CAUSE_ISSUED')->index();
            $t->decimal('amount_paid', 15, 2)->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('ejectment_proceedings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->nullable()->constrained('applications')->cascadeOnDelete();
            $t->foreignId('property_id')->nullable()->constrained('properties')->cascadeOnDelete();
            $t->string('proceeding_no', 60)->unique();
            $t->text('ground_of_ejectment');
            $t->date('show_cause_issued_on');
            // Clause 21(a): a period of not less than seven days.
            $t->unsignedSmallInteger('show_cause_days')->default(7);
            $t->date('show_cause_deadline');
            $t->longText('cause_shown')->nullable();
            $t->date('cause_shown_on')->nullable();
            $t->foreignId('hearing_id')->nullable()->constrained('hearings')->nullOnDelete();
            $t->boolean('is_satisfied_with_cause')->nullable();
            $t->date('ejectment_order_date')->nullable();
            $t->longText('ejectment_order_text')->nullable();
            // Clause 21(c): not more than sixty days to vacate.
            $t->unsignedSmallInteger('vacation_period_days')->nullable();
            $t->date('vacate_by')->nullable();
            $t->date('vacated_on')->nullable();
            $t->enum('status', ['SHOW_CAUSE_ISSUED', 'CAUSE_RECEIVED', 'HEARING',
                                'ORDER_PASSED', 'VACATED', 'DROPPED', 'UNDER_APPEAL'])
              ->default('SHOW_CAUSE_ISSUED')->index();
            $t->unsignedBigInteger('initiated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->string('auditable_type', 100)->nullable();
            $t->unsignedBigInteger('auditable_id')->nullable();
            $t->string('table_name', 80)->nullable();
            $t->string('event', 40);          // created / updated / deleted / viewed / downloaded
            $t->json('old_values')->nullable();
            $t->json('new_values')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('user_name', 150)->nullable();
            $t->string('user_role', 60)->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->string('url', 500)->nullable();
            $t->string('method', 10)->nullable();
            $t->text('description')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['auditable_type', 'auditable_id'], 'idx_auditable');
            $t->index(['user_id', 'created_at'], 'idx_audit_user_time');
            $t->index('event', 'idx_audit_event');
        });

        Schema::create('notifications_queue', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->foreignId('application_id')->nullable()->constrained('applications')->cascadeOnDelete();
            $t->enum('channel', ['IN_APP', 'EMAIL', 'SMS'])->default('IN_APP');
            $t->string('recipient', 191)->nullable();
            $t->string('subject', 200)->nullable();
            $t->text('body');
            $t->string('category', 60)->nullable();
            $t->string('action_url', 500)->nullable();
            $t->enum('status', ['QUEUED', 'SENT', 'FAILED', 'READ'])->default('QUEUED')->index();
            $t->unsignedSmallInteger('attempts')->default(0);
            $t->text('last_error')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('report_snapshots', function (Blueprint $t) {
            $t->id();
            $t->string('report_code', 60)->index();
            $t->string('title', 200);
            $t->json('parameters')->nullable();
            $t->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
            $t->string('file_path', 255)->nullable();
            $t->string('format', 20)->default('PDF');
            $t->unsignedBigInteger('generated_by')->nullable();
            $t->timestamp('generated_at')->useCurrent();
            $t->char('content_hash', 64)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_snapshots');
        Schema::dropIfExists('notifications_queue');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('ejectment_proceedings');
        Schema::dropIfExists('penalties');
    }
};
