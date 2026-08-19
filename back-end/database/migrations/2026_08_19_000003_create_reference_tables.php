<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference / configuration masters.
 *
 * Every statutory number lives in `settings` with effective-from dating so a
 * fresh SRO can be absorbed without a code change (MASTER_PLAN risk R8 — the
 * Scheme was amended in 2000, 2001, 2006 and 2024).
 *
 * Area conversion factors are data, not constants, because a Marla is
 * 272.25 sqft under the revenue system but 225 sqft in most urban housing
 * schemes — a 21% swing that lands directly in the rent (risk R2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key', 80)->index();
            $t->text('value');
            $t->enum('value_type', ['STRING', 'INT', 'DECIMAL', 'DATE', 'BOOL', 'JSON'])
              ->default('STRING');
            $t->string('group', 50)->default('general')->index();
            $t->string('label', 150);
            $t->text('description')->nullable();
            $t->string('legal_reference', 150)->nullable();
            $t->date('effective_from');
            $t->date('effective_to')->nullable();
            $t->boolean('is_editable')->default(true);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['key', 'effective_from'], 'uq_setting_key_effective');
        });

        Schema::create('unit_conversion_profiles', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name', 120);
            $t->text('description')->nullable();
            $t->boolean('is_default')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('unit_conversion_factors', function (Blueprint $t) {
            $t->id();
            $t->foreignId('unit_profile_id')->constrained('unit_conversion_profiles')->cascadeOnDelete();
            $t->string('unit_code', 20);
            $t->string('unit_name', 60);
            $t->string('unit_name_ur', 80)->nullable();
            // sqft per one of this unit. 18,4 keeps 272.25 and 1,089,000 exact.
            $t->decimal('sqft_per_unit', 18, 4);
            $t->unsignedSmallInteger('display_order')->default(0);
            // Marks the units usable in a compound entry, e.g. "2 Kanal 7 Marla 3 Sarsai".
            $t->boolean('is_compound_component')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['unit_profile_id', 'unit_code'], 'uq_profile_unit');
        });

        Schema::create('document_types', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('name', 150);
            $t->string('name_ur', 200)->nullable();
            $t->string('category', 60)->default('EVIDENCE')->index();
            $t->text('description')->nullable();
            $t->boolean('is_certified_copy_required')->default(false);
            $t->boolean('is_mandatory')->default(false);
            // Clause 3(ii)(c) allows a court order to stand in for the ordinary
            // evidence bundle, so mandatory-ness must be waivable with a reason.
            $t->boolean('is_waivable')->default(true);
            $t->boolean('proves_possession_date')->default(false);
            $t->string('allowed_mime', 255)->default('application/pdf,image/jpeg,image/png,image/tiff');
            $t->unsignedInteger('max_size_kb')->default(10240);
            $t->unsignedSmallInteger('display_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('rate_sources', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('name', 150);
            $t->text('description')->nullable();
            // The DO-determined rate is the operative figure; the rest are supporting record.
            $t->boolean('is_operative')->default(false);
            $t->boolean('requires_reference_no')->default(false);
            $t->boolean('requires_reasons')->default(false);
            $t->unsignedSmallInteger('display_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::table('districts', function (Blueprint $t) {
            $t->foreign('unit_profile_id')
              ->references('id')->on('unit_conversion_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('districts', function (Blueprint $t) {
            $t->dropForeign(['unit_profile_id']);
        });
        Schema::dropIfExists('rate_sources');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('unit_conversion_factors');
        Schema::dropIfExists('unit_conversion_profiles');
        Schema::dropIfExists('settings');
    }
};
