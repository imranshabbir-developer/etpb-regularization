@extends('layouts.app')

@section('title', 'Reference data')
@section('heading', 'Reference data')

@section('content')

<div class="page-head">
    <h1>Reference data</h1>
    <p class="lede">Geography, measurement standards, document heads and rate sources.</p>
</div>

<div class="tiles">
    @foreach ($counts as $label => $n)
        <div class="tile">
            <div class="tile-label">{{ ucfirst($label) }}</div>
            <div class="tile-value">{{ number_format($n) }}</div>
            @if ($label === 'mouzas' && $n === 0)
                <div class="tile-sub">awaiting the Board of Revenue list</div>
            @endif
        </div>
    @endforeach
</div>

@if (($counts['mouzas'] ?? 0) === 0)
    <div class="alert alert-warn">
        @include('partials.icon', ['name' => 'alert'])
        <div>
            <p class="mb-0">
                <strong>No mouza list is loaded.</strong> Applicants can still type a mouza name
                free-hand, and it is kept against the property, but it is not validated against a
                revenue master. Loading the Board of Revenue mouza list for the districts in scope
                would close that gap.
            </p>
        </div>
    </div>
@endif

{{-- ---------- Measurement standards ---------- --}}
<div class="card">
    <div class="card-head">
        <h3>Measurement standards</h3>
        <div class="card-actions"><span class="clause">Affects every rent</span></div>
    </div>
    <div class="card-body">
        <p class="muted text-[.9rem]">
            A Marla is 272.25 sqft on the revenue standard and 225 sqft in most urban housing
            schemes &mdash; a 21% difference that carries straight into the assessed rent.
            The factors used are frozen onto each application when the area is recorded, so
            changing a district here never restates an assessment already made.
        </p>

        <div class="grid-2">
            @foreach ($profiles as $p)
                <div>
                    <div class="inline-list">
                        <strong>{{ $p->name }}</strong>
                        @if ($p->is_default)<span class="badge badge-good">Default</span>@endif
                    </div>
                    <div class="table-wrap mt-1">
                        <table class="data">
                            <thead><tr><th>Unit</th><th class="num">Square feet</th></tr></thead>
                            <tbody>
                            @foreach ($factors[$p->id] ?? [] as $f)
                                <tr>
                                    <td>{{ $f->unit_name }}</td>
                                    <td class="num">{{ rtrim(rtrim($f->sqft_per_unit, '0'), '.') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ---------- Districts ---------- --}}
<div class="card">
    <div class="card-head">
        <h3>Districts</h3>
        <span class="badge badge-neutral">{{ number_format($districts->total()) }}</span>
    </div>
    <div class="table-wrap border-0 rounded-none">
        <table class="data">
            <thead>
            <tr><th>District</th><th>Province</th><th>Measurement standard</th><th></th></tr>
            </thead>
            <tbody>
            @foreach ($districts as $d)
                <tr>
                    <td>{{ $d->name }}</td>
                    <td>{{ $d->province?->name }}</td>
                    <td>
                        <span class="badge badge-{{ $d->unitProfile?->code === 'REVENUE' ? 'good' : 'gold' }}">
                            {{ $d->unitProfile?->code ?? 'Not set' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('admin.masters.district', $d) }}"
                              class="flex gap-1 items-center justify-end">
                            @csrf
                            <select name="unit_profile_id" class="select"
                                    style="font-size:.78rem;padding:.2rem .4rem;width:auto">
                                @foreach ($profiles as $p)
                                    <option value="{{ $p->id }}" @selected($d->unit_profile_id === $p->id)>
                                        {{ $p->code }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="btn btn-outline btn-sm" type="submit">Set</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if ($districts->hasPages())
        <div class="card-foot">{{ $districts->links() }}</div>
    @endif
</div>

<div class="grid-2 items-start gap-[1.15rem]">
    {{-- ---------- Document heads ---------- --}}
    <div class="card">
        <div class="card-head">
            <h3>Evidence heads</h3>
            <div class="card-actions"><span class="clause">Head 2</span></div>
        </div>
        <div class="table-wrap border-0 rounded-none">
            <table class="data">
                <thead><tr><th>Document</th><th>Required</th><th>Certified</th></tr></thead>
                <tbody>
                @foreach ($documentTypes as $t)
                    <tr>
                        <td>{{ $t->name }}</td>
                        <td>{!! $t->is_mandatory ? '<span class="badge badge-danger">Yes</span>' : '<span class="faint">No</span>' !!}</td>
                        <td>{!! $t->is_certified_copy_required ? '<span class="badge badge-gold">Yes</span>' : '<span class="faint">No</span>' !!}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ---------- Rate sources ---------- --}}
    <div class="card">
        <div class="card-head">
            <h3>Rate sources</h3>
            <div class="card-actions"><span class="clause">Head 3</span></div>
        </div>
        <div class="table-wrap border-0 rounded-none">
            <table class="data">
                <thead><tr><th>Source</th><th>Operative</th></tr></thead>
                <tbody>
                @foreach ($rateSources as $r)
                    <tr>
                        <td>{{ $r->name }}</td>
                        <td>{!! $r->is_operative ? '<span class="badge badge-gold">The DO rate</span>' : '<span class="faint">Supporting</span>' !!}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
