<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sync role ↔ permission matrix for an already-seeded database after the
 * November 2026 meeting changes (staff document upload, Chairman corrections).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $ensure = function (string $code, string $name, string $module) use ($now): int {
            $id = DB::table('permissions')->where('code', $code)->value('id');
            if ($id) {
                return (int) $id;
            }

            return (int) DB::table('permissions')->insertGetId([
                'code'       => $code,
                'name'       => $name,
                'module'     => $module,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        $correctId = $ensure('records.correct', 'Correct wrong entries / modify records (Chairman only)', 'approvals');
        $uploadId = $ensure('documents.upload', 'Upload evidence documents', 'documents');
        $updateId = $ensure('applications.update', 'Update an application', 'applications');

        $attach = function (string $roleCode, array $permIds) use ($now): void {
            $roleId = DB::table('roles')->where('code', $roleCode)->value('id');
            if (! $roleId) {
                return;
            }
            foreach ($permIds as $pid) {
                $exists = DB::table('role_permission')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $pid)
                    ->exists();
                if (! $exists) {
                    DB::table('role_permission')->insert([
                        'role_id'       => $roleId,
                        'permission_id' => $pid,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }
        };

        $attach('CHAIRMAN', [$correctId, $uploadId, $updateId]);
        $attach('ADMINISTRATOR', [$uploadId, $updateId]);
        $attach('DISTRICT_OFFICER', [$uploadId, $updateId]);
        $attach('DEALING_ASSISTANT', [$uploadId, $updateId]);
    }

    public function down(): void
    {
        // Keep permissions; removing mid-flight would break live sessions.
    }
};
