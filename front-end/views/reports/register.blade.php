@extends('layouts.app')

@section('title', $title)
@section('heading', $title)

@section('content')

<div class="page-head">
    <h1>{{ $title }}</h1>
    <p class="lede">As at {{ $generatedAt->format('d F Y, H:i') }}. Up to 2,000 rows.</p>
</div>

<div class="card no-print">
    <div class="card-body tight">
        <div class="btn-row">
            @foreach ($registers as $code => $label)
                <a href="{{ route('reports.registers', $code) }}"
                   class="btn btn-{{ $code === $register ? 'primary' : 'outline' }} btn-sm">{{ $label }}</a>
            @endforeach
        </div>
        <div class="mt-2">
            @include('partials.report-formats', ['route' => route('reports.registers', $register)])
        </div>
    </div>
</div>

<div class="card">
    <div class="card-head"><h3>{{ number_format($rows->count()) }} rows</h3></div>

    @if ($rows->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'empty'])
            <p class="mb-0">Nothing to show in this register.</p>
        </div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0;max-height:70vh;overflow:auto">
            <table class="data">
                <thead>
                <tr>
                    @foreach (array_keys((array) $rows->first()) as $col)
                        <th>{{ ucwords(str_replace('_', ' ', $col)) }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ((array) $row as $col => $value)
                            <td class="{{ is_numeric($value) ? 'num' : '' }}">
                                @if (is_numeric($value) && str_contains($col, 'amount') || str_contains($col, 'arrears') || str_contains($col, 'rent'))
                                    {{ number_format((float) $value, 2) }}
                                @elseif (is_null($value) || $value === '')
                                    <span class="faint">&mdash;</span>
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
