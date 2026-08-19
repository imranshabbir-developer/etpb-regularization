@extends('layouts.app')

@section('title', 'Statutory settings')
@section('heading', 'Statutory settings')

@section('content')

<div class="page-head">
    <h1>Statutory settings</h1>
    <p class="lede">
        Every figure the Scheme fixes lives here with an effective-from date, so an amending
        SRO can be absorbed without touching the code.
    </p>
</div>

<div class="alert alert-info">
    @include('partials.icon', ['name' => 'info'])
    <div>
        <p class="mb-0">
            Changing a setting <strong>does not overwrite it</strong>. The old value is closed off
            on the day before the new one takes effect and kept, so an assessment made under the
            old rule can still be reproduced exactly.
        </p>
    </div>
</div>

@foreach ($groups as $group => $settings)
    <div class="card">
        <div class="card-head">
            <h3>{{ ucwords(str_replace('_', ' ', $group)) }}</h3>
            <span class="badge badge-neutral">{{ count($settings) }}</span>
        </div>
        <div class="card-body">
            @foreach ($settings as $key => $s)
                <div class="py-2">
                    <div class="flex flex-wrap items-baseline gap-2">
                        <strong class="text-[.92rem]">{{ $s->label }}</strong>
                        @if ($s->legal_reference)
                            <span class="clause">{{ $s->legal_reference }}</span>
                        @endif
                        @unless ($s->is_editable)
                            <span class="badge badge-neutral" title="Fixed by statute">Fixed by statute</span>
                        @endunless
                    </div>

                    <div class="flex flex-wrap items-center gap-3 mt-1">
                        <code class="text-[.85rem] font-mono bg-[var(--tint)] px-2 py-1 rounded">{{ $s->value }}</code>
                        <span class="faint text-[.78rem]">
                            in force since
                            {{ \Illuminate\Support\Carbon::parse($s->effective_from)->format('d-m-Y') }}
                        </span>
                    </div>

                    @if ($s->description)
                        <p class="muted text-[.84rem] mt-1 mb-0">{{ $s->description }}</p>
                    @endif

                    @if ($s->is_editable)
                        <details class="mt-2">
                            <summary class="cursor-pointer text-[.84rem] text-pk-600">Change this</summary>
                            <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-2">
                                @csrf
                                <input type="hidden" name="key" value="{{ $s->key }}">
                                <div class="grid-3">
                                    <div class="field">
                                        <label for="v_{{ $s->key }}">New value</label>
                                        <input type="text" id="v_{{ $s->key }}" name="value" class="input"
                                               value="{{ $s->value }}" required maxlength="2000">
                                    </div>
                                    <div class="field">
                                        <label for="d_{{ $s->key }}">Effective from</label>
                                        <input type="date" id="d_{{ $s->key }}" name="effective_from"
                                               class="input" value="{{ now()->toDateString() }}" required>
                                    </div>
                                    <div class="field flex items-end">
                                        <button class="btn btn-primary btn-sm" type="submit">Supersede</button>
                                    </div>
                                </div>
                            </form>
                        </details>
                    @endif
                </div>
                @if (! $loop->last)<hr class="divider my-1">@endif
            @endforeach
        </div>
    </div>
@endforeach

@if ($history->isNotEmpty())
    <div class="card">
        <div class="card-head"><h3>Superseded values</h3></div>
        <div class="table-wrap border-0 rounded-none">
            <table class="data">
                <thead>
                <tr><th>Setting</th><th>Value</th><th>In force from</th><th>Until</th></tr>
                </thead>
                <tbody>
                @foreach ($history as $h)
                    <tr class="row-muted">
                        <td>{{ $h->label }}</td>
                        <td class="font-mono text-[.82rem]">{{ $h->value }}</td>
                        <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($h->effective_from)->format('d-m-Y') }}</td>
                        <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($h->effective_to)->format('d-m-Y') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
