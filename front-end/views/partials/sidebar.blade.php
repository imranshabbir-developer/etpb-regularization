@php
    /** @var \App\Models\User|null $u */
    $u = auth()->user();
    $isApplicant = $u && $u->isApplicant();

    /**
     * Navigation is built per role rather than shown-and-hidden, because a
     * member of the public filing one application should not be looking at an
     * officer's work queues. Each entry is [route, label, icon, permission].
     * A null permission means everyone signed in sees it.
     */
    $groups = $isApplicant
        ? [
            'My application' => [
                ['dashboard',    'Home',              'home',  null],
                ['apply.start',  'Apply',             'plus',  'applications.create'],
                ['apply.mine',   'My applications',   'file',  'applications.view_own'],
            ],
        ]
        : [
            'Case work' => [
                ['dashboard',          'Dashboard',       'home',       null],
                ['applications.index', 'All applications','file',       'applications.view_district'],
                ['apply.start',        'File for a walk-in','plus',     'applications.create'],
            ],
            'My queues' => [
                // Accounts and the District Officer share this screen but come to
                // it for different reasons, so it is named for whoever is looking.
                ['queue.scrutiny',
                    $u && $u->hasPermission('applications.scrutinise') ? 'Scrutiny' : 'Deposits to confirm',
                    $u && $u->hasPermission('applications.scrutinise') ? 'doc-search' : 'cash',
                    'applications.scrutinise|fee.verify'],
                ['queue.assessment', 'Rent assessment', 'scale',      'assessment.propose'],
                ['queue.objections', 'Objections',      'inbox',      'objections.decide'],
                ['queue.arrears',    'Arrears',         'cash',       'arrears.view'],
                ['queue.litigation', 'Litigation',      'gavel',      'litigation.view'],
                ['queue.approvals',  'Pending approval','check',      'approvals.administrator'],
            ],
            'Reports' => [
                ['reports.glimpse',   'At a glance',       'chart', 'reports.executive'],
                ['reports.executive', 'Consolidated report','list',  'reports.executive'],
                ['reports.registers', 'Registers',         'file',  'reports.registers'],
            ],
            'Administration' => [
                ['admin.users',    'Users & roles',      'users',  'users.manage'],
                ['admin.masters',  'Reference data',     'map',    'masters.manage'],
                ['admin.settings', 'Statutory settings', 'cog',    'settings.manage'],
                ['admin.audit',    'Audit log',          'shield', 'audit.view'],
            ],
        ];
@endphp

<nav class="sidebar pk-stripe" id="sidebar" aria-label="Main navigation">

    <div class="sidebar-mobile-head">
        <span class="brand-mark">@include('partials.icon', ['name' => 'shield'])</span>
        <span class="brand-text">
            <strong>Regularization</strong>
            <span>ETPB</span>
        </span>
    </div>

    @foreach ($groups as $groupName => $items)
        @php
            $visible = collect($items)->filter(function ($i) use ($u) {
                if (! Route::has($i[0])) {
                    return false;
                }
                if ($i[3] === null) {
                    return true;
                }
                // "a|b" means the user needs either one.
                return $u && $u->hasAnyPermission(...explode('|', $i[3]));
            });
        @endphp

        @if ($visible->isNotEmpty())
            <div class="sidebar-group">
                <h6>{{ $groupName }}</h6>
                @foreach ($visible as [$route, $label, $icon, $perm])
                    <a href="{{ route($route) }}"
                       class="nav-link {{ request()->routeIs($route) ? 'is-active' : '' }}"
                       @if (request()->routeIs($route)) aria-current="page" @endif>
                        @include('partials.icon', ['name' => $icon])
                        <span>{{ $label }}</span>
                        @if (! empty($badges[$route]))
                            <span class="nav-badge">{{ $badges[$route] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    @endforeach

    @if ($isApplicant)
        <div class="sidebar-group">
            <h6>Help</h6>
            <div class="px-4 pb-2" style="padding-inline-start: calc(var(--stripe-w) + .95rem)">
                <p class="text-[.78rem] text-pk-200 leading-snug mb-2">
                    Not sure about something? Use <strong>Ask about the scheme</strong> at the
                    bottom of the screen.
                </p>
            </div>
        </div>
    @endif
</nav>
