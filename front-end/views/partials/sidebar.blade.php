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
            __('My application') => [
                ['dashboard',    __('Home'),              'home',  null],
                ['apply.start',  __('Apply'),             'plus',  'applications.create'],
                ['apply.mine',   __('My applications'),   'file',  'applications.view_own'],
            ],
        ]
        : [
            __('Case work') => [
                ['dashboard',          __('Dashboard'),       'home',       null],
                ['applications.index', __('All applications'),'file',       'applications.view_district|applications.view_all'],
                ['apply.start',        __('File for a walk-in'),'plus',     'applications.create'],
            ],
            __('My queues') => [
                // Accounts and the District Officer share this screen but come to
                // it for different reasons, so it is named for whoever is looking.
                ['queue.scrutiny',
                    $u && $u->hasPermission('applications.scrutinise') ? __('Scrutiny') : __('Deposits to confirm'),
                    $u && $u->hasPermission('applications.scrutinise') ? 'doc-search' : 'cash',
                    'applications.scrutinise|fee.verify'],
                ['queue.assessment', __('Rent assessment'), 'scale',      'assessment.propose'],
                ['queue.objections', __('Objections'),      'inbox',      'objections.decide'],
                ['queue.arrears',    __('Arrears'),         'cash',       'arrears.view'],
                ['queue.litigation', __('Litigation'),      'gavel',      'litigation.view'],
                ['queue.approvals',  __('Pending approval'),'check',      'approvals.administrator'],
            ],
            __('Reports') => [
                ['reports.glimpse',   __('At a glance'),       'chart', 'reports.executive'],
                ['reports.executive', __('Consolidated report'),'list',  'reports.executive'],
                ['reports.registers', __('Registers'),         'file',  'reports.registers'],
            ],
            __('Administration') => [
                ['admin.users',    __('Users & roles'),      'users',  'users.manage'],
                ['admin.masters',  __('Reference data'),     'map',    'masters.manage'],
                ['admin.settings', __('Statutory settings'), 'cog',    'settings.manage'],
                ['admin.audit',    __('Audit log'),          'shield', 'audit.view'],
            ],
        ];
@endphp

<nav class="sidebar" id="sidebar" aria-label="{{ __('Main navigation') }}">
    <div class="sidebar-glow" aria-hidden="true">
        <span class="sidebar-glow-orb sidebar-glow-orb-a"></span>
        <span class="sidebar-glow-orb sidebar-glow-orb-b"></span>
    </div>

    <div class="sidebar-body">
    <div class="sidebar-mobile-head">
        <span class="brand-mark">@include('partials.icon', ['name' => 'shield'])</span>
        <span class="brand-text">
            <strong>{{ __('Regularization') }}</strong>
            <span>{{ __('ETPB') }}</span>
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
            <h6>{{ __('Help') }}</h6>
            <div class="px-3 pb-2">
                <p class="text-[.78rem] text-pk-200 leading-snug mb-2">
                    {!! __('Not sure about something? Use <strong>Ask about the scheme</strong> at the bottom of the screen.') !!}
                </p>
            </div>
        </div>
    @endif
    </div>
</nav>
