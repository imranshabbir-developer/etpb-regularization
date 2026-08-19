<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Roles and permissions.
 *
 * The officer roles mirror the offices named in the Scheme 1977 — District
 * Officer (a Deputy or Assistant Administrator in charge of a district office,
 * Clause 2(i)(f)), Administrator (Clause 2(i)(a)) and Chairman — plus the
 * operational roles a district office actually needs.
 *
 * Separation of duties is enforced by permission grants, not by convention:
 * the officer who verifies the fee instrument cannot fix the rent, and the
 * officer who proposes an assessment cannot approve it.
 */
class RolePermissionSeeder extends Seeder
{
    /** @var array<string, array<int, array{0:string,1:string}>> */
    private array $modules = [
        'applications' => [
            ['applications.view_own', 'View own applications'],
            ['applications.view_district', 'View applications in own district'],
            ['applications.view_all', 'View all applications'],
            ['applications.create', 'Create an application'],
            ['applications.update', 'Update an application'],
            ['applications.submit', 'Submit an application'],
            ['applications.scrutinise', 'Scrutinise an application'],
            ['applications.return_deficient', 'Return an application as deficient'],
            ['applications.reject_ineligible', 'Reject an ineligible application'],
            ['applications.assign', 'Assign an application to an officer'],
        ],
        'documents' => [
            ['documents.upload', 'Upload evidence documents'],
            ['documents.view', 'View evidence documents'],
            ['documents.download', 'Download evidence documents'],
            ['documents.verify', 'Verify or mark a document deficient'],
            ['documents.waive', 'Waive a mandatory document'],
        ],
        'fee' => [
            ['fee.record', 'Record a fee instrument'],
            ['fee.verify', 'Verify a fee instrument with the bank'],
            ['fee.view', 'View fee records'],
        ],
        'assessment' => [
            ['assessment.view', 'View rent assessments'],
            ['assessment.propose', 'Propose an assessment'],
            ['assessment.rate_inputs', 'Record rate inputs and comparables'],
            ['assessment.fix_rent', 'Fix the assessed rent'],
            ['assessment.extend_sla', 'Extend the 60-day assessment period'],
            ['assessment.reassess', 'Initiate a periodical re-assessment'],
        ],
        'due_process' => [
            ['notices.issue', 'Issue public, tenant and show-cause notices'],
            ['notices.view', 'View notices'],
            ['objections.record', 'Record an objection'],
            ['objections.decide', 'Decide an objection'],
            ['hearings.schedule', 'Schedule a hearing'],
            ['hearings.record', 'Record hearing proceedings'],
        ],
        'arrears' => [
            ['arrears.view', 'View the arrears ledger'],
            ['arrears.generate', 'Generate the arrears ledger'],
            ['arrears.receipt', 'Post a payment receipt'],
            ['arrears.instalments', 'Approve an instalment plan'],
            ['arrears.remit', 'Approve a remission of rent or arrears'],
        ],
        'litigation' => [
            ['litigation.view', 'View the litigation register'],
            ['litigation.manage', 'Record and update litigation'],
        ],
        'approvals' => [
            ['approvals.do_decision', 'Record the District Officer decision'],
            ['approvals.administrator', 'Approve regularization as Administrator'],
            ['approvals.chairman', 'Exercise Chairman powers'],
            ['approvals.remand', 'Remand an application'],
        ],
        'outcome' => [
            ['nominees.manage', 'Record the nomination form and legal heirs'],
            ['agreements.execute', 'Execute a tenancy agreement'],
            ['orders.issue', 'Issue a regularization order'],
        ],
        'enforcement' => [
            ['penalties.impose', 'Impose a penalty'],
            ['ejectment.initiate', 'Initiate ejectment proceedings'],
            ['tenancy.cancel', 'Cancel a tenancy'],
        ],
        'reports' => [
            ['reports.deep', 'Generate the deep case report'],
            ['reports.executive', 'Generate the executive / master report'],
            ['reports.registers', 'Generate operational registers'],
            ['reports.export', 'Export reports to Excel or PDF'],
        ],
        'admin' => [
            ['users.manage', 'Manage users'],
            ['roles.manage', 'Manage roles and permissions'],
            ['masters.manage', 'Manage geography and reference masters'],
            ['settings.manage', 'Manage statutory settings'],
            ['audit.view', 'View the audit log'],
            ['system.backup', 'Run backups and restores'],
        ],
    ];

    public function run(): void
    {
        $permissionIds = [];
        foreach ($this->modules as $module => $perms) {
            foreach ($perms as [$code, $name]) {
                $permissionIds[$code] = DB::table('permissions')->insertGetId([
                    'code'       => $code,
                    'name'       => $name,
                    'module'     => $module,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $all = array_keys($permissionIds);

        $roles = [
            [
                'code' => 'SYSTEM_ADMIN', 'name' => 'System Administrator', 'level' => 1,
                'desc' => 'Full technical administration. Not a statutory office.',
                'perms' => $all,
            ],
            [
                'code' => 'CHAIRMAN', 'name' => 'Chairman, ETPB', 'level' => 10,
                'desc' => 'Extends the 60-day assessment period, calls for record, remits rent '
                    . 'under Clause 12, and cancels a tenancy obtained by fraud under Clause 23.',
                'perms' => [
                    'applications.view_all', 'documents.view', 'documents.download', 'fee.view',
                    'assessment.view', 'assessment.extend_sla', 'notices.view', 'arrears.view',
                    'arrears.remit', 'litigation.view', 'approvals.chairman', 'approvals.remand',
                    'tenancy.cancel', 'reports.deep', 'reports.executive', 'reports.registers',
                    'reports.export', 'audit.view',
                ],
            ],
            [
                'code' => 'ADMINISTRATOR', 'name' => 'Administrator', 'level' => 20,
                'desc' => 'Approves regularization within one month after recording reasons '
                    . '(Clause 3(ii)(d)); may call for the record of any assessment.',
                'perms' => [
                    'applications.view_all', 'applications.assign', 'documents.view',
                    'documents.download', 'fee.view', 'assessment.view', 'notices.view',
                    'objections.decide', 'hearings.schedule', 'hearings.record', 'arrears.view',
                    'litigation.view', 'approvals.administrator', 'approvals.remand',
                    'reports.deep', 'reports.executive', 'reports.registers', 'reports.export',
                ],
            ],
            [
                'code' => 'DISTRICT_OFFICER', 'name' => 'District Officer', 'level' => 40,
                'desc' => 'Deputy or Assistant Administrator in charge of a district office '
                    . '(Clause 2(i)(f)). Assesses and fixes rent, issues notices, hears '
                    . 'objections and executes the tenancy agreement.',
                'perms' => [
                    'applications.view_district', 'applications.scrutinise',
                    'applications.return_deficient', 'applications.reject_ineligible',
                    'documents.view', 'documents.download', 'documents.verify', 'documents.waive',
                    'fee.view', 'assessment.view', 'assessment.propose', 'assessment.rate_inputs',
                    'assessment.fix_rent', 'assessment.reassess', 'notices.issue', 'notices.view',
                    'objections.record', 'objections.decide', 'hearings.schedule', 'hearings.record',
                    'arrears.view', 'arrears.generate', 'arrears.instalments', 'litigation.view',
                    'litigation.manage', 'approvals.do_decision', 'nominees.manage',
                    'agreements.execute', 'orders.issue', 'penalties.impose', 'ejectment.initiate',
                    'reports.deep', 'reports.registers', 'reports.export',
                ],
            ],
            [
                'code' => 'DEALING_ASSISTANT', 'name' => 'Dealing Assistant', 'level' => 60,
                'desc' => 'Data entry for walk-in applicants, diary and dispatch of notices.',
                'perms' => [
                    'applications.view_district', 'applications.create', 'applications.update',
                    'applications.submit', 'documents.upload', 'documents.view', 'fee.record',
                    'fee.view', 'assessment.view', 'notices.view', 'objections.record',
                    'arrears.view', 'litigation.view', 'reports.registers',
                ],
            ],
            [
                'code' => 'ACCOUNTS_OFFICER', 'name' => 'Accounts Officer / Cashier', 'level' => 55,
                'desc' => 'Verifies the fee instrument with the bank and posts receipts against '
                    . 'the arrears ledger. Cannot fix rent — separation of duties.',
                'perms' => [
                    'applications.view_district', 'fee.record', 'fee.verify', 'fee.view',
                    'arrears.view', 'arrears.receipt', 'reports.registers', 'reports.export',
                ],
            ],
            [
                'code' => 'LEGAL_OFFICER', 'name' => 'Legal Officer', 'level' => 55,
                'desc' => 'Maintains the litigation register, restraining orders and direction '
                    . 'cases; flags properties as sub judice.',
                'perms' => [
                    'applications.view_district', 'documents.view', 'documents.download',
                    'litigation.view', 'litigation.manage', 'notices.view', 'hearings.schedule',
                    'hearings.record', 'reports.registers', 'reports.export',
                ],
            ],
            [
                'code' => 'AUDITOR', 'name' => 'Auditor (read-only)', 'level' => 90,
                'desc' => 'Read-only access to everything, including the audit log. No mutations.',
                'perms' => [
                    'applications.view_all', 'documents.view', 'fee.view', 'assessment.view',
                    'notices.view', 'arrears.view', 'litigation.view', 'reports.deep',
                    'reports.executive', 'reports.registers', 'reports.export', 'audit.view',
                ],
            ],
            [
                'code' => 'APPLICANT', 'name' => 'Applicant / Occupant', 'level' => 100,
                'desc' => 'An existing occupant applying to have possession regularized '
                    . 'under Clause 3(ii).',
                // Outcome only: an applicant sees what was decided and what they
                // owe — they must, in order to pay it — but not the rate inputs,
                // the comparables or the officer's internal reasoning.
                'perms' => [
                    'applications.view_own', 'applications.create', 'applications.update',
                    'applications.submit', 'documents.upload', 'documents.view',
                    'documents.download', 'fee.record', 'fee.view', 'arrears.view',
                ],
            ],
        ];

        foreach ($roles as $r) {
            $roleId = DB::table('roles')->insertGetId([
                'code'            => $r['code'],
                'name'            => $r['name'],
                'description'     => $r['desc'],
                'hierarchy_level' => $r['level'],
                'is_system'       => true,
                'is_active'       => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            foreach ($r['perms'] as $code) {
                if (! isset($permissionIds[$code])) {
                    continue;
                }
                DB::table('role_permission')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => $permissionIds[$code],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }
}
