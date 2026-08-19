<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Applicant and property records.
 *
 * Fields follow the requirements spec verbatim: name, parentage, address,
 * CNIC, contact for the applicant; property/sub-unit no., area and location
 * for the property.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('full_name', 150);
            $t->string('full_name_ur', 200)->nullable();
            $t->enum('parentage_type', ['FATHER', 'HUSBAND'])->default('FATHER');
            $t->string('parentage_name', 150);
            $t->string('cnic', 13)->index();
            $t->string('contact', 20);
            $t->string('alternate_contact', 20)->nullable();
            $t->string('email', 150)->nullable();
            $t->enum('gender', ['MALE', 'FEMALE', 'OTHER'])->nullable();
            $t->date('date_of_birth')->nullable();
            $t->text('postal_address');
            $t->foreignId('address_district_id')->nullable()->constrained('districts')->nullOnDelete();
            $t->string('photo_path', 255)->nullable();
            $t->string('thumb_impression_path', 255)->nullable();
            // Clause 12 grounds for remission of rent / arrears.
            $t->boolean('is_indigent')->default(false);
            $t->boolean('is_widow')->default(false);
            $t->boolean('is_orphan')->default(false);
            $t->boolean('is_verified')->default(false);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('properties', function (Blueprint $t) {
            $t->id();
            $t->string('property_no', 60);
            $t->string('sub_unit_no', 60)->nullable();   // optional per the spec
            $t->enum('property_type', ['HOUSE', 'SHOP', 'BUILDING', 'PLOT', 'AGRI_LAND', 'OTHER'])
              ->default('HOUSE');   // Scheme 2(i)(m)
            $t->enum('usage_type', ['RESIDENTIAL', 'COMMERCIAL', 'RESIDENTIAL_CUM_COMMERCIAL', 'OTHER'])
              ->default('RESIDENTIAL');
            $t->text('address');
            $t->foreignId('province_id')->constrained('provinces')->restrictOnDelete();
            $t->foreignId('district_id')->constrained('districts')->restrictOnDelete();
            $t->foreignId('tehsil_id')->nullable()->constrained('tehsils')->nullOnDelete();
            $t->foreignId('mouza_id')->nullable()->constrained('mouzas')->nullOnDelete();
            $t->string('city', 120)->nullable();
            // Revenue identifiers
            $t->string('khewat_no', 40)->nullable();
            $t->string('khatooni_no', 40)->nullable();
            $t->string('khasra_no', 40)->nullable();
            $t->text('boundaries')->nullable();
            $t->boolean('is_rural_agricultural')->default(false);  // Scheme 2(i)(o)
            $t->text('land_reforms_note')->nullable();             // MASTER_PLAN 1.3
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['property_no', 'sub_unit_no', 'district_id'], 'idx_property_identity');
        });

        Schema::create('property_areas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $t->foreignId('unit_profile_id')->constrained('unit_conversion_profiles')->restrictOnDelete();
            $t->enum('entry_mode', ['SINGLE', 'COMPOUND'])->default('SINGLE');
            $t->string('entered_unit_code', 20)->nullable();
            $t->decimal('entered_value', 18, 4)->nullable();
            // Compound entry: "2 Kanal 7 Marla 3 Sarsai"
            $t->decimal('acres', 18, 4)->nullable();
            $t->decimal('kanals', 18, 4)->nullable();
            $t->decimal('marlas', 18, 4)->nullable();
            $t->decimal('sarsais', 18, 4)->nullable();
            $t->decimal('square_yards', 18, 4)->nullable();
            $t->decimal('square_feet_direct', 18, 4)->nullable();
            // Canonical value everything else derives from.
            $t->decimal('area_sqft', 18, 4);
            $t->decimal('covered_area_sqft', 18, 4)->nullable();
            // Frozen copy of the factors used, so a later factor edit cannot
            // silently restate a historic assessment.
            $t->json('conversion_trace')->nullable();
            $t->boolean('is_current')->default(true);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('property_geo_tags', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $t->decimal('latitude', 10, 7);
            $t->decimal('longitude', 10, 7);
            $t->decimal('accuracy_meters', 8, 2)->nullable();
            $t->enum('source', ['GPS_DEVICE', 'MOBILE', 'MANUAL', 'SATELLITE', 'SURVEY'])->default('MANUAL');
            $t->json('polygon')->nullable();
            $t->string('image_path', 255)->nullable();
            $t->text('remarks')->nullable();
            $t->timestamp('captured_at')->nullable();
            $t->unsignedBigInteger('captured_by')->nullable();
            $t->boolean('is_primary')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_geo_tags');
        Schema::dropIfExists('property_areas');
        Schema::dropIfExists('properties');
        Schema::dropIfExists('applicants');
    }
};
