@extends('layouts.print')

@section('doc-title', $title)
@section('doc-subject', $title)

@section('doc-body')

@php
    /*
     * Short official headings so a landscape A4 sheet can hold every column
     * without clipping. Values themselves are unchanged — only the label and
     * wrapping are tightened for paper.
     */
    $headingMap = [
        'application_no'         => 'Application No.',
        'full_name'              => 'Name',
        'cnic'                   => 'CNIC',
        'district'               => 'District',
        'instrument_type'        => 'Type',
        'instrument_no'          => 'Instr. No.',
        'instrument_date'        => 'Date',
        'amount'                 => 'Amount',
        'bank_name'              => 'Bank',
        'bank'                   => 'Bank / Branch',
        'branch_code'            => 'Br.',
        'instrument_status'      => 'Status',
        'payment_status'         => 'Payment',
        'assessed_monthly_rent'  => 'Monthly Rent',
        'total_arrears'          => 'Arrears',
        'arrears_paid'           => 'Paid',
        'arrears_balance'        => 'Balance',
        'status'                 => 'Status',
        'objection_no'           => 'Objection No.',
        'objector_name'          => 'Objector',
        'objector_cnic'          => 'Objector CNIC',
        'filed_on'               => 'Filed',
        'is_within_time'         => 'In Time',
        'court_name'             => 'Court',
        'case_no'                => 'Case No.',
        'case_type'              => 'Case Type',
        'is_pending'             => 'Pending',
        'has_restraining_order'  => 'Stay',
        'is_direction_case'      => 'Direction',
        'next_hearing_date'      => 'Next Hearing',
        'outcome'                => 'Outcome',
        'regularized_at'         => 'Regularized',
        'agreement_no'           => 'Agreement No.',
        'executed_on'            => 'Executed',
        'round_no'               => 'Round',
        'base_date'              => 'Base Date',
        'enhancement_rate'       => 'Enh. %',
        'enhancement_method'     => 'Method',
        'proposed_monthly_rent'  => 'Proposed',
        'determined_monthly_rent'=> 'Determined',
        'first_notice_date'      => '1st Notice',
        'completion_due_date'    => 'Due',
        'notice_no'              => 'Notice No.',
        'notice_type'            => 'Notice Type',
        'issued_on'              => 'Issued',
        'served_on'              => 'Served',
        'service_mode'           => 'Service',
        'objection_deadline'     => 'Obj. Deadline',
        'hearing_no'             => 'Hearing No.',
        'scheduled_for'          => 'Scheduled',
        'venue'                  => 'Venue',
        'presiding_officer'      => 'Presiding',
        'presiding_designation'  => 'Designation',
        'monthly_rent'           => 'Monthly Rent',
        'security_amount'        => 'Security',
        'effective_from'         => 'Effective',
        'created_at'             => 'Filed On',
    ];

    $moneyCols = ['amount', 'arrears', 'rent', 'security', 'balance', 'paid', 'proposed', 'determined'];
    $isMoney = function (string $col) use ($moneyCols): bool {
        foreach ($moneyCols as $needle) {
            if (str_contains($col, $needle)) {
                return true;
            }
        }

        return false;
    };

    $formatValue = function (string $col, mixed $value) use ($isMoney): string {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if ($isMoney($col) && is_numeric($value)) {
            return number_format((float) $value, 2);
        }
        if (in_array($col, ['is_within_time', 'is_pending', 'has_restraining_order', 'is_direction_case'], true)) {
            if ($value === 1 || $value === '1' || $value === true) {
                return 'Yes';
            }
            if ($value === 0 || $value === '0' || $value === false) {
                return 'No';
            }
        }
        // Soft-break long identifiers so Dompdf can wrap them inside the cell.
        $text = (string) $value;
        if (in_array($col, ['application_no', 'agreement_no', 'notice_no', 'objection_no', 'hearing_no', 'case_no'], true)) {
            return str_replace(['/', '-'], ["/\u{200B}", "-\u{200B}"], $text);
        }
        if (str_contains($col, 'type') || str_contains($col, 'status') || str_contains($col, 'method') || str_contains($col, 'mode')) {
            $aliases = [
                'DEMAND_DRAFT' => 'DD',
                'PAY_ORDER' => 'PO',
                'BANKERS_CHEQUE' => 'BC',
                'BANKER_CHEQUE' => 'BC',
                'CHEQUE' => 'CQ',
                'VERIFIED' => 'Verified',
                'PENDING' => 'Pending',
                'REJECTED' => 'Rejected',
                'PAID' => 'Paid',
                'UNPAID' => 'Unpaid',
            ];

            return $aliases[$text] ?? str_replace('_', ' ', $text);
        }

        return $text;
    };

    $columns = $rows->isEmpty() ? [] : array_keys((array) $rows->first());
    $wide = count($columns) >= 8;

    // Explicit widths (sum 100%) stop Dompdf from overshooting the page edge.
    $widthHints = [
        'application_no' => 15,
        'full_name' => 11,
        'cnic' => 9,
        'district' => 8,
        'instrument_type' => 4,
        'instrument_no' => 8,
        'instrument_date' => 7,
        'amount' => 7,
        'bank' => 12,
        'bank_name' => 10,
        'branch_code' => 4,
        'instrument_status' => 7,
        'payment_status' => 7,
        'status' => 8,
        'assessed_monthly_rent' => 8,
        'total_arrears' => 8,
        'arrears_paid' => 7,
        'arrears_balance' => 8,
        'created_at'             => 8,
        'round_no'               => 4,
        'base_date'              => 7,
        'enhancement_rate'       => 5,
        'enhancement_method'     => 6,
        'proposed_monthly_rent'  => 7,
        'determined_monthly_rent'=> 7,
        'first_notice_date'      => 7,
        'completion_due_date'    => 7,
        'notice_no'              => 8,
        'notice_type'            => 7,
        'issued_on'              => 7,
        'served_on'              => 7,
        'service_mode'           => 6,
        'objection_deadline'     => 7,
        'hearing_no'             => 7,
        'scheduled_for'          => 8,
        'venue'                  => 7,
        'presiding_officer'      => 9,
        'presiding_designation'  => 8,
        'objection_no'           => 8,
        'objector_name'          => 9,
        'objector_cnic'          => 9,
        'filed_on'               => 7,
        'is_within_time'         => 5,
        'court_name'             => 10,
        'case_no'                => 8,
        'case_type'              => 7,
        'is_pending'             => 5,
        'has_restraining_order'  => 5,
        'is_direction_case'      => 5,
        'next_hearing_date'      => 7,
        'outcome'                => 8,
        'regularized_at'         => 8,
        'agreement_no'           => 9,
        'executed_on'            => 7,
        'monthly_rent'           => 7,
        'security_amount'        => 7,
        'effective_from'         => 7,
    ];
    $colWidths = [];
    if ($wide && $columns !== []) {
        $raw = [];
        foreach ($columns as $col) {
            $raw[$col] = $widthHints[$col] ?? max(6, (int) floor(100 / count($columns)));
        }
        $sum = array_sum($raw) ?: 1;
        foreach ($raw as $col => $w) {
            $colWidths[$col] = round(($w / $sum) * 100, 2);
        }
    }
@endphp

<h1>{{ $title }}</h1>
<p class="muted">{{ number_format($rows->count()) }} rows.</p>

@if ($rows->isEmpty())
    <p class="muted">Nothing to show in this register.</p>
@else
    <table class="t{{ $wide ? ' wide' : '' }}">
        @if ($colWidths !== [])
            <colgroup>
                @foreach ($columns as $col)
                    <col style="width:{{ $colWidths[$col] }}%" />
                @endforeach
            </colgroup>
        @endif
        <thead>
        <tr>
            @foreach ($columns as $col)
                <th class="{{ $isMoney($col) ? 'num' : '' }}">
                    {{ $headingMap[$col] ?? ucwords(str_replace('_', ' ', $col)) }}
                </th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            <tr>
                @foreach ((array) $row as $col => $value)
                    <td class="{{ $isMoney($col) ? 'num' : '' }}">
                        {{ $formatValue($col, $value) }}
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

@endsection
