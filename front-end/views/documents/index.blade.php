@extends('layouts.app')

@section('title', __('Evidence of possession'))
@section('heading', __('Evidence — :no', ['no' => $application->application_no]))

@section('content')

@php
    $mandatory = $types->where('is_mandatory', true);
    $satisfied = $mandatory->filter(function ($t) use ($uploaded) {
        $docs = $uploaded->get($t->id);
        return $docs && $docs->whereIn('status', ['PENDING', 'VERIFIED', 'WAIVED'])->isNotEmpty();
    });
@endphp

<div class="page-head">
    <h1>{{ __('Evidence of possession') }}</h1>
    <p class="lede">
        {{ __('Regularization is made on the basis of documentary evidence, or on a court order.') }}
        {!! __('Documents marked below must be filed as <strong>certified copies</strong>.') !!}
    </p>
    <div class="inline-list mt-1">
        <a href="{{ route('applications.show', $application) }}" class="badge badge-neutral">&larr; {{ __('Case file') }}</a>
        <span class="badge badge-{{ $satisfied->count() === $mandatory->count() ? 'good' : 'warn' }}">
            {{ __(':done of :total required heads filed', ['done' => $satisfied->count(), 'total' => $mandatory->count()]) }}
        </span>
        <span class="clause">{{ __('Clause 3(ii)(c)') }}</span>
    </div>
</div>

<div class="grid-2" style="gap:1.15rem;align-items:start">
    <div>
        <div class="card">
            <div class="card-head">
                <h3>{{ __('Documents') }}</h3>
                <span class="badge badge-neutral">{{ __(':count uploaded', ['count' => $application->documents->count()]) }}</span>
            </div>
            <div class="card-body">
                @foreach ($types as $type)
                    @php $docs = $uploaded->get($type->id); @endphp
                    <div style="padding:.55rem 0">
                        <div class="inline-list">
                            <strong style="font-size:.9rem">{{ __($type->name) }}</strong>
                            @if ($type->is_mandatory)
                                <span class="badge badge-danger">{{ __('Required') }}</span>
                            @endif
                            @if ($type->is_certified_copy_required)
                                <span class="badge badge-gold">{{ __('Certified copy') }}</span>
                            @endif
                            @if (! $docs || $docs->isEmpty())
                                <span class="badge badge-neutral">{{ __('Not filed') }}</span>
                            @endif
                        </div>

                        @if ($docs && $docs->isNotEmpty())
                            <div class="table-wrap mt-1">
                                <table class="data">
                                    <tbody>
                                    @foreach ($docs as $doc)
                                        <tr>
                                            <td>
                                                <a href="{{ route('documents.download', $doc) }}">{{ $doc->title }}</a>
                                                <div class="faint" style="font-size:.75rem">
                                                    {{ $doc->original_filename }} &middot;
                                                    {{ __(':size KB', ['size' => number_format($doc->size_bytes / 1024, 0)]) }}
                                                    @if ($doc->reference_no) &middot; {{ __('ref :no', ['no' => $doc->reference_no]) }} @endif
                                                    @if ($doc->document_date)
                                                        &middot; {{ \Illuminate\Support\Carbon::parse($doc->document_date)->translatedFormat('d-m-Y') }}
                                                    @endif
                                                </div>
                                                @if ($doc->issuing_authority)
                                                    <div class="faint" style="font-size:.75rem">{{ __('Issued by :authority', ['authority' => $doc->issuing_authority]) }}</div>
                                                @endif
                                                <div class="faint" style="font-size:.7rem;font-family:var(--font-mono)"
                                                     title="{{ __('SHA-256, so later substitution is detectable') }}">
                                                    {{ substr($doc->sha256, 0, 24) }}…
                                                </div>
                                                @if ($doc->verification_remarks)
                                                    <div class="muted" style="font-size:.78rem;margin-top:.25rem">
                                                        {{ $doc->verification_remarks }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-end nowrap" style="width:1%">
                                                @php $tone = match ($doc->status) {
                                                    'VERIFIED' => 'good', 'DEFICIENT', 'REJECTED' => 'danger',
                                                    'WAIVED' => 'gold', default => 'info',
                                                }; @endphp
                                                <span class="badge badge-{{ $tone }}">{{ __(ucfirst(strtolower($doc->status))) }}</span>
                                                @if (! $doc->is_certified_copy && $type->is_certified_copy_required)
                                                    <div><span class="badge badge-warn">{{ __('Not certified') }}</span></div>
                                                @endif

                                                @can('do', 'documents.verify')
                                                    @if ($doc->status === 'PENDING')
                                                        <form method="POST" action="{{ route('documents.verify', $doc) }}"
                                                              style="margin-top:.4rem;text-align:start">
                                                            @csrf
                                                            <select name="action" class="select" style="font-size:.78rem;padding:.2rem .4rem">
                                                                <option value="VERIFIED">{{ __('Verify') }}</option>
                                                                <option value="DEFICIENT">{{ __('Deficient') }}</option>
                                                                <option value="REJECTED">{{ __('Reject') }}</option>
                                                                @if ($type->is_waivable)
                                                                    <option value="WAIVED">{{ __('Waive') }}</option>
                                                                @endif
                                                            </select>
                                                            <input type="text" name="remarks" class="input"
                                                                   style="font-size:.78rem;padding:.2rem .4rem;margin-top:.25rem"
                                                                   placeholder="{{ __('Remarks') }}" required minlength="5">
                                                            <button class="btn btn-outline btn-sm" type="submit"
                                                                    style="margin-top:.25rem">{{ __('Record') }}</button>
                                                        </form>
                                                    @endif
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    @if (! $loop->last)<hr class="divider" style="margin:.5rem 0">@endif
                @endforeach
            </div>
        </div>
    </div>

    <div>
        @can('do', 'documents.upload')
            <div class="card">
                <div class="card-head"><h3>{{ __('Upload a document') }}</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('documents.store', $application) }}"
                          enctype="multipart/form-data">
                        @csrf

                        <div class="field">
                            <label for="document_type_id">{{ __('Head') }} <span class="req">*</span></label>
                            <select id="document_type_id" name="document_type_id" class="select" required>
                                @foreach ($types as $t)
                                    <option value="{{ $t->id }}"
                                            data-certified="{{ $t->is_certified_copy_required ? 1 : 0 }}">
                                        {{ __($t->name) }}@if ($t->is_mandatory) — {{ __('required') }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="file">{{ __('Upload file') }} <span class="req">*</span></label>
                            @include('partials.file-dropzone', [
                                'name' => 'file',
                                'id' => 'file',
                                'required' => true,
                                'accept' => '.pdf,.jpg,.jpeg,.png,.tif,.tiff',
                                'title' => __('Drop a file here or click to browse'),
                                'subtitle' => __('PDF, JPEG, PNG or TIFF'),
                                'hint' => __('Up to :size MB. Type is checked from the file itself, not its extension.', ['size' => number_format($maxKb / 1024, 0)]),
                            ])
                        </div>

                        <div class="field" style="display:flex;align-items:flex-start;gap:.45rem">
                            <input type="checkbox" id="is_certified_copy" name="is_certified_copy" value="1"
                                   style="margin:.3rem 0 0">
                            <label for="is_certified_copy" style="margin:0;font-weight:500">
                                {!! __('This is a <strong>certified copy</strong>') !!}
                            </label>
                        </div>

                        <div class="field">
                            <label for="title">{{ __('Title') }}</label>
                            <input type="text" id="title" name="title" class="input" maxlength="200"
                                   placeholder="{{ __('Defaults to the head name') }}">
                        </div>

                        <div class="grid-2">
                            <div class="field">
                                <label for="reference_no">{{ __('Reference no.') }}</label>
                                <input type="text" id="reference_no" name="reference_no" class="input" maxlength="100">
                            </div>
                            <div class="field">
                                <label for="document_date">{{ __('Document date') }}</label>
                                <input type="date" id="document_date" name="document_date" class="input"
                                       max="{{ now()->toDateString() }}">
                            </div>
                        </div>

                        <div class="field">
                            <label for="issuing_authority">{{ __('Issuing authority') }}</label>
                            <input type="text" id="issuing_authority" name="issuing_authority" class="input"
                                   maxlength="200" placeholder="{{ __('e.g. Patwari Halqa, Tehsil Office') }}">
                        </div>

                        <button class="btn btn-primary" type="submit" style="width:100%">
                            @include('partials.icon', ['name' => 'plus']) {{ __('Upload') }}
                        </button>
                    </form>
                </div>
            </div>
        @endcan

        <div class="card">
            <div class="card-head"><h3>{{ __('Required heads') }}</h3></div>
            <div class="card-body">
                <ul class="timeline">
                    @foreach ($mandatory as $t)
                        @php
                            $docs = $uploaded->get($t->id);
                            $done = $docs && $docs->whereIn('status', ['PENDING', 'VERIFIED', 'WAIVED'])->isNotEmpty();
                        @endphp
                        <li class="{{ $done ? 'is-done' : '' }}">
                            <div class="t-title">{{ __($t->name) }}</div>
                            @if ($t->is_certified_copy_required)
                                <div class="t-meta">{{ __('Certified copy required') }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection
