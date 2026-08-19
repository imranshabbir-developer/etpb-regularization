<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The client's central business rule, in their own words:
 *
 *   "if the amount is not paid then the application status will be marked as
 *    pending and the application which payment is not made by the applicant
 *    the same application will not be process. After depositing the requisite
 *    amount of Rs. 5000/- PKR his status will be marked as 'paid' and when the
 *    status of payment is changed the govt officers / officials then process
 *    his application in accordance with law."
 *
 * Payment state therefore lives on the application itself, not buried in the
 * fee record, so that an officer scanning a list can see at a glance who has
 * paid and every processing screen can refuse an unpaid file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $t) {
            $t->enum('payment_status', ['PENDING', 'PAID'])
              ->default('PENDING')
              ->after('status')
              ->index();
            $t->timestamp('payment_confirmed_at')->nullable()->after('payment_status');
            $t->unsignedBigInteger('payment_confirmed_by')->nullable()->after('payment_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $t) {
            $t->dropColumn(['payment_status', 'payment_confirmed_at', 'payment_confirmed_by']);
        });
    }
};
