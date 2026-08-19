<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RBAC — roles, permissions and the ETPB-specific columns on users.
 * Roles mirror the offices named in the Scheme 1977 (District Officer,
 * Administrator, Chairman) plus operational support roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('name', 100);
            $t->string('name_ur', 150)->nullable();
            $t->text('description')->nullable();
            $t->unsignedSmallInteger('hierarchy_level')->default(50);
            $t->boolean('is_system')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('code', 80)->unique();
            $t->string('name', 120);
            $t->string('module', 60)->index();
            $t->text('description')->nullable();
            $t->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $t) {
            $t->id();
            $t->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $t->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['role_id', 'permission_id'], 'uq_role_permission');
        });

        Schema::create('user_role', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $t->unsignedBigInteger('assigned_by')->nullable();
            $t->timestamp('assigned_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'role_id'], 'uq_user_role');
        });

        Schema::table('users', function (Blueprint $t) {
            $t->string('cnic', 13)->nullable()->unique()->after('email');
            $t->string('designation', 120)->nullable()->after('cnic');
            $t->string('contact', 20)->nullable()->after('designation');
            $t->unsignedBigInteger('office_id')->nullable()->index()->after('contact');
            $t->unsignedBigInteger('district_id')->nullable()->index()->after('office_id');
            $t->enum('status', ['ACTIVE', 'SUSPENDED', 'LOCKED', 'INACTIVE'])
              ->default('ACTIVE')->after('district_id');
            $t->boolean('force_password_change')->default(true)->after('status');
            $t->timestamp('password_changed_at')->nullable()->after('force_password_change');
            $t->string('two_factor_secret')->nullable()->after('password_changed_at');
            $t->boolean('two_factor_enabled')->default(false)->after('two_factor_secret');
            $t->timestamp('last_login_at')->nullable()->after('two_factor_enabled');
            $t->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $t->unsignedSmallInteger('failed_login_count')->default(0)->after('last_login_ip');
            $t->timestamp('locked_until')->nullable()->after('failed_login_count');
            $t->softDeletes();
        });

        Schema::create('login_attempts', function (Blueprint $t) {
            $t->id();
            $t->string('identifier', 191)->index();
            $t->string('ip_address', 45)->index();
            $t->string('user_agent', 255)->nullable();
            $t->boolean('successful')->default(false);
            $t->string('failure_reason', 120)->nullable();
            $t->timestamp('attempted_at')->useCurrent();
            $t->index(['identifier', 'attempted_at'], 'idx_login_identifier_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn([
                'cnic', 'designation', 'contact', 'office_id', 'district_id', 'status',
                'force_password_change', 'password_changed_at', 'two_factor_secret',
                'two_factor_enabled', 'last_login_at', 'last_login_ip',
                'failed_login_count', 'locked_until', 'deleted_at',
            ]);
        });
        Schema::dropIfExists('user_role');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
