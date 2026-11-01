@extends('layouts.app')

@section('title', 'Evidence')
@section('heading', 'Step 4 of 6 — Evidence')

@section('content')

<div class="container-narrow">
    @include('partials.wizard-steps')

    <div class="page-head">
        <h1>Evidence of your possession</h1>
        <p class="lede">
            {{ $application->application_no }} &mdash; you may <strong>skip this step</strong>.
            Attach what you have; the office can upload the rest later.
        </p>
        <div class="inline-list mt-1">
            <span class="badge badge-info">{{ $application->documents->count() }} attached</span>
            <span class="badge badge-neutral">All optional</span>
            <span class="clause">Clause 3(ii)(c)</span>
        </div>
    </div>

    <div class="essentials-banner">
        <span aria-hidden="true">→</span>
        <div>
            Nothing on this page is mandatory to continue.
            Use <strong>Continue</strong> below if you have nothing to attach right now.
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>Attach a document</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('documents.store', $application) }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="grid-2">
                    <div class="field">
                        <label for="document_type_id">Which document is this? <span class="req">*</span></label>
                        <select id="document_type_id" name="document_type_id" class="select" required>
                            @foreach ($types as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <p class="hint">Optional evidence — none of these are mandatory to continue.</p>
                    </div>

                    <div class="field">
                        <label for="file">Upload the document <span class="req">*</span></label>
                        @include('partials.file-dropzone', [
                            'name' => 'file',
                            'id' => 'file',
                            'required' => true,
                            'accept' => '.pdf,.jpg,.jpeg,.png,.tif,.tiff',
                            'title' => 'Drop a document here or click to browse',
                            'subtitle' => 'PDF or a clear photo',
                            'hint' => 'Up to 10 MB. Only needed when you attach a document now — you may skip this whole step.',
                        ])
                    </div>
                </div>

                <div class="field flex items-start gap-2">
                    <input type="checkbox" id="is_certified_copy" name="is_certified_copy" value="1" class="mt-1">
                    <label for="is_certified_copy" class="m-0 font-medium">
                        This is a <strong>certified copy</strong>
                    </label>
                </div>

                <details class="mb-4">
                    <summary class="cursor-pointer text-[.88rem] muted">
                        Add reference details (optional)
                    </summary>
                    <div class="grid-3 mt-3">
                        <div class="field">
                            <label for="reference_no">Reference number</label>
                            <input type="text" id="reference_no" name="reference_no" class="input" maxlength="100">
                        </div>
                        <div class="field">
                            <label for="document_date">Date on the document</label>
                            <input type="date" id="document_date" name="document_date" class="input"
                                   max="{{ now()->toDateString() }}">
                        </div>
                        <div class="field">
                            <label for="issuing_authority">Who issued it</label>
                            <input type="text" id="issuing_authority" name="issuing_authority"
                                   class="input" maxlength="200" placeholder="e.g. Patwari Halqa">
                        </div>
                    </div>
                </details>

                <button class="btn btn-primary" type="submit">
                    @include('partials.icon', ['name' => 'plus']) Attach this document
                </button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>What you have attached</h3>
            <span class="badge badge-neutral">{{ $application->documents->count() }}</span>
        </div>
        <div class="card-body">
            @foreach ($types as $type)
                @php $docs = $uploaded->get($type->id); @endphp
                <div class="flex flex-wrap items-start gap-2 justify-between py-2">
                    <div class="min-w-0">
                        <strong class="text-[.9rem]">{{ $type->name }}</strong>
                        <span class="badge badge-neutral">Optional</span>
                        @if ($type->is_certified_copy_required)<span class="badge badge-gold">Certified copy</span>@endif

                        @if ($docs && $docs->isNotEmpty())
                            <ul class="list-none p-0 m-0 mt-1">
                                @foreach ($docs as $doc)
                                    <li class="text-[.82rem] faint">
                                        <a href="{{ route('documents.download', $doc) }}">{{ $doc->original_filename }}</a>
                                        &middot; {{ number_format($doc->size_bytes / 1024, 0) }} KB
                                        &middot; <span class="badge badge-{{ $doc->status === 'VERIFIED' ? 'good' : ($doc->status === 'DEFICIENT' ? 'danger' : 'info') }}">{{ ucfirst(strtolower($doc->status)) }}</span>
                                        @if ($doc->verification_remarks)
                                            <div class="muted">{{ $doc->verification_remarks }}</div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div>
                        @if (! $docs || $docs->isEmpty())
                            <span class="badge badge-neutral">Not attached</span>
                        @else
                            <span class="badge badge-good">{{ $docs->count() }} attached</span>
                        @endif
                    </div>
                </div>
                @if (! $loop->last)<hr class="divider my-1">@endif
            @endforeach
        </div>
        <div class="card-foot">
            <div class="wizard-actions mt-0 pt-0 border-0">
                <a href="{{ route('apply.mine') }}" class="btn btn-ghost">Save and finish later</a>
                <span class="spacer"></span>
                <a href="{{ route('apply.occupants', $application) }}" class="btn btn-primary btn-lg">
                    Continue @include('partials.icon', ['name' => 'arrow-right'])
                </a>
            </div>
            <p class="hint mt-2 mb-0">
                You may continue with no documents. Missing papers can be uploaded later by you,
                or by the Administrator / District Officer / one-window desk from the case file.
            </p>
        </div>
    </div>
</div>

@endsection
