{{--
    Download buttons for a report, in the three formats an office actually uses:
    a PDF to file and sign, a Word document to amend before it goes up the chain,
    and a spreadsheet to sort and total.

    Usage:
      @include('partials.report-formats', ['route' => route('reports.glimpse'), 'query' => ['district' => 3]])
--}}
@php
    $query = $query ?? [];
    $link = function (string $format) use ($route, $query) {
        $sep = str_contains($route, '?') ? '&' : '?';
        return $route . $sep . http_build_query(array_merge($query, ['format' => $format]));
    };
@endphp

<div class="btn-row no-print" role="group" aria-label="Download this report">
    <a href="{{ $link('pdf') }}" class="btn btn-outline btn-sm">
        @include('partials.icon', ['name' => 'file']) PDF
    </a>
    <a href="{{ $link('docx') }}" class="btn btn-outline btn-sm">
        @include('partials.icon', ['name' => 'file']) MS Word
    </a>
    <a href="{{ $link('xlsx') }}" class="btn btn-outline btn-sm">
        @include('partials.icon', ['name' => 'chart']) Excel
    </a>
    <button type="button" class="btn btn-ghost btn-sm" onclick="window.print()">
        @include('partials.icon', ['name' => 'file']) Print
    </button>
</div>
