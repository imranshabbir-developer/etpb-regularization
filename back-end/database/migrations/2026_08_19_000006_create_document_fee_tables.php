<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evidence of possession (certified copies) and the Rs. 5,000 processing fee.
 *
 * Clause 3(ii)(c): regularization is made on the basis of documentary evidence
 * or on a court order. Files are hashed on upload so later tampering is
 * detectable (MASTER_PLAN risk R5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->foreignId('document_type_id')->constrained('document_types')->restrictOnDelete();
            $t->string('title', 200);
            $t->string('file_path', 255);
            $t->string('original_filename', 255);
            $t->string('mime_type', 100);
            $t->unsignedBigInteger('size_bytes');
            $t->char('sha256', 64)->index();
            $t->boolean('is_certified_copy')->default(false);
            $t->string('issuing_authority', 200)->nullable();
            $t->date('document_date')->nullable();
            $t->string('reference_no', 100)->nullable();
            $t->text('remarks')->nullable();
            $t->enum('status', ['PENDING', 'VERIFIED', 'DEFICIENT', 'REJECTED', 'WAIVED'])
              ->default('PENDING')->index();
            $t->unsignedBigInteger('verified_by')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->text('verification_remarks')->nullable();
            $t->unsignedBigInteger('uploaded_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['application_id', 'document_type_id'], 'idx_doc_app_type');
        });

        Schema::create('document_verifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_document_id')->constrained('application_documents')->cascadeOnDelete();
            $t->enum('action', ['VERIFIED', 'MARKED_DEFICIENT', 'REJECTED', 'WAIVED', 'RE_OPENED']);
            $t->text('remarks')->nullable();
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->string('actor_role', 40)->nullable();
            $t->timestamp('acted_at')->useCurrent();
        });

        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->enum('instrument_type', ['PAY_ORDER', 'BANKERS_CHEQUE', 'DEMAND_DRAFT'])
              ->default('PAY_ORDER');
            $t->string('instrument_no', 60);
            $t->date('instrument_date');
            $t->decimal('amount', 15, 2)->default(5000.00);
            $t->string('payee', 150)->default('Chairman ETPB');
            $t->string('bank_name', 150);
            $t->string('branch_name', 150);
            $t->string('branch_code', 30)->nullable();
            $t->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            // Depositor particulars, as required by the spec.
            $t->string('depositor_name', 150);
            $t->string('depositor_cnic', 13);
            $t->string('depositor_contact', 20);
            $t->date('submission_date');
            $t->string('scan_path', 255)->nullable();
            $t->enum('status', ['PENDING', 'VERIFIED', 'BOUNCED', 'REJECTED', 'REFUNDED'])
              ->default('PENDING')->index();
            $t->unsignedBigInteger('verified_by')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->string('bank_confirmation_ref', 100)->nullable();
            $t->text('verification_remarks')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('attachments', function (Blueprint $t) {
            $t->id();
            $t->string('attachable_type', 100);
            $t->unsignedBigInteger('attachable_id');
            $t->string('title', 200)->nullable();
            $t->string('file_path', 255);
            $t->string('original_filename', 255);
            $t->string('mime_type', 100);
            $t->unsignedBigInteger('size_bytes');
            $t->char('sha256', 64)->index();
            $t->unsignedBigInteger('uploaded_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['attachable_type', 'attachable_id'], 'idx_attachable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('document_verifications');
        Schema::dropIfExists('application_documents');
    }
};
