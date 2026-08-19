/** Shared role and page table, used by uicheck.mjs and darkcheck.mjs. */
/**
 * Real case numbers, not guesses. A hard-coded id that has been deleted turns
 * into a wall of 404s that looks like a broken application when it is only a
 * broken test, so these come from the seeded data and are checked before use.
 */
const CASE           = Number(process.env.ETPB_CASE           || 12);  // mid-process, Lahore
const OBJECTION_CASE = Number(process.env.ETPB_OBJECTION_CASE || 15);  // objection window open
const SUB_JUDICE_CASE= Number(process.env.ETPB_SUB_JUDICE     || 27);  // stayed by a court
const APPROVAL_CASE  = Number(process.env.ETPB_APPROVAL_CASE  || 9);   // awaiting the Administrator

const caseFile = id => ['', '/assessment', '/due-process', '/arrears', '/occupancy',
                        '/documents', '/fee', '/completion'].map(s => `/applications/${id}${s}`);

export const ROLES = {
    applicant: {
        email: 'demo.applicant@example.com', password: 'Demo#Portal2026',
        pages: ['/dashboard', '/apply', '/apply/about-you', '/apply/property', '/apply/possession', '/my-applications'],
    },
    // Someone who has not applied yet. The dashboard and "my applications" take
    // a different path for them — the empty state that a first-time member of
    // the public actually meets — and nothing else here exercises it.
    newApplicant: {
        email: 'imran.shabbir@example.com', password: 'Imran@Portal2026',
        pages: ['/dashboard', '/my-applications', '/apply', '/apply/about-you'],
    },
    dealing: {
        email: 'da.lhr@etpb.gov.pk',
        pages: ['/dashboard', '/applications', '/apply'],
    },
    accounts: {
        email: 'accounts.lhr@etpb.gov.pk',
        pages: ['/dashboard', '/queues/scrutiny', '/queues/arrears'],
    },
    officer: {
        email: 'do.lhr@etpb.gov.pk',
        pages: ['/dashboard', '/applications', ...caseFile(CASE), ...caseFile(OBJECTION_CASE),
                `/applications/${SUB_JUDICE_CASE}`, `/applications/${SUB_JUDICE_CASE}/due-process`,
                '/queues/scrutiny', '/queues/assessment', '/queues/objections',
                '/queues/arrears', '/queues/litigation', '/reports/registers'],
    },
    administrator: {
        email: 'admin.lhr@etpb.gov.pk',
        pages: ['/dashboard', '/approvals', `/applications/${APPROVAL_CASE}/approval`,
                '/reports/executive', '/reports/glimpse'],
    },
    chairman: {
        email: 'chairman@etpb.gov.pk',
        pages: ['/dashboard', '/reports/glimpse', '/reports/executive', '/reports/registers/arrears'],
    },
    sysadmin: {
        email: 'admin@etpb.gov.pk',
        pages: ['/dashboard', '/admin/users', '/admin/reference-data', '/admin/settings', '/admin/audit'],
    },
};

