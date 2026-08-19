<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Geography hierarchy: Province > Division > District > Tehsil > Mouza,
 * plus ETPB district/zonal offices. Required by the requirements spec
 * ("we need to get information where property exists like his Mouza,
 * City, Tehsil District, Province").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $t) {
            $t->id();
            $t->string('code', 10)->unique();
            $t->string('name', 100);
            $t->string('name_ur', 150)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('divisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('province_id')->constrained('provinces')->restrictOnDelete();
            $t->string('code', 15)->unique();
            $t->string('name', 100);
            $t->string('name_ur', 150)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('districts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('province_id')->constrained('provinces')->restrictOnDelete();
            $t->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $t->string('code', 15)->unique();
            $t->string('name', 100);
            $t->string('name_ur', 150)->nullable();
            // Which marla standard applies here — see MASTER_PLAN 5.1 / risk R2.
            $t->unsignedBigInteger('unit_profile_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('tehsils', function (Blueprint $t) {
            $t->id();
            $t->foreignId('district_id')->constrained('districts')->restrictOnDelete();
            $t->string('code', 20)->unique();
            $t->string('name', 100);
            $t->string('name_ur', 150)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('mouzas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tehsil_id')->constrained('tehsils')->restrictOnDelete();
            $t->string('name', 150);
            $t->string('name_ur', 200)->nullable();
            $t->string('hadbast_no', 30)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['tehsil_id', 'name'], 'idx_mouza_tehsil_name');
        });

        Schema::create('offices', function (Blueprint $t) {
            $t->id();
            $t->string('code', 25)->unique();
            $t->string('name', 150);
            $t->enum('office_type', ['HEAD_OFFICE', 'ZONAL', 'DISTRICT'])->default('DISTRICT');
            $t->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $t->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $t->string('address', 255)->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('email', 150)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offices');
        Schema::dropIfExists('mouzas');
        Schema::dropIfExists('tehsils');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('divisions');
        Schema::dropIfExists('provinces');
    }
};
