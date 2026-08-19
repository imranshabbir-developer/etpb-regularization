@extends('layouts.print')

@section('doc-title', $title)
@section('doc-subject', $title)

@section('doc-body')

<h1>{{ $title }}</h1>
<p class="muted">{{ number_format($rows->count()) }} rows.</p>

@if ($rows->isEmpty())
    <p class="muted">Nothing to show in this register.</p>
@else
    @php $columns = array_keys((array) $rows->first()); @endphp
    <table class="t">
        <thead>
        <tr>
            @foreach ($columns as $col)
                <th class="{{ str_contains($col, 'amount') || str_contains($col, 'arrears') || str_contains($col, 'rent') ? 'num' : '' }}">
                    {{ ucwords(str_replace('_', ' ', $col)) }}
                </th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            <tr>
                @foreach ((array) $row as $col => $value)
                    @php
                        $money = str_contains($col, 'amount') || str_contains($col, 'arrears')
                                 || str_contains($col, 'rent');
                    @endphp
                    <td class="{{ $money ? 'num' : '' }}">
                        @if ($value === null || $value === '')
                            &mdash;
                        @elseif ($money && is_numeric($value))
                            {{ number_format((float) $value, 2) }}
                        @elseif (is_bool($value))
                            {{ $value ? 'Yes' : 'No' }}
                        @else
                            {{ $value }}
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

@endsection
