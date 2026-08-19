<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>@yield('doc-title', 'Report') — ETPB</title>
<style>
/*
   Official document styling.

   Laid out the way a Punjab government office lays out a report on paper: a
   centred letterhead, a rule, the reference number and date on one line, an
   underlined SUBJECT, the body, and a signature block above a distribution
   list. Serif throughout, because official correspondence here is set in
   serif and a sans-serif report reads as a printout rather than a document.

   Plain CSS only — Dompdf and PhpWord each parse a narrow subset, so nothing
   relies on flexbox, grid, custom properties or modern colour syntax. Layout
   that has to survive both is done with tables, which both render faithfully.
*/
@page { margin: 22mm 18mm 20mm 20mm; }

body {
    font-family: "DejaVu Serif", "Times New Roman", serif;
    font-size: 10pt;
    line-height: 1.5;
    color: #000000;
    margin: 0;
}

/* ---- letterhead -------------------------------------------------------- */

.lh            { text-align: center; margin-bottom: 2pt; }
.lh .bismillah { font-size: 11pt; color: #01411C; margin-bottom: 4pt; }
.lh .govt      { font-size: 12pt; font-weight: bold; letter-spacing: 2pt; color: #01411C; }
.lh .dept      { font-size: 15pt; font-weight: bold; letter-spacing: 0.6pt; margin-top: 1pt; }
.lh .scheme    { font-size: 9pt; font-style: italic; margin-top: 2pt; }
.lh .addr      { font-size: 8pt; margin-top: 2pt; }

/* The flag rule: Pakistan green with the white hoist band that stands for
   the country's religious minorities, whose properties this Board holds. */
/* Drawn as two cells rather than a div with a wide left border: Dompdf
   renders a thick border on a zero-content block inconsistently. */
table.rule-flag      { width: 100%; border-collapse: collapse; margin: 7pt 0 3pt; }
table.rule-flag td   { height: 4pt; padding: 0; border: 0.4pt solid #7A8F86; font-size: 1pt; }
table.rule-flag td.w { width: 16pt; background-color: #FFFFFF; }
table.rule-flag td.g { background-color: #01411C; }
.rule-thin { border-bottom: 0.7pt solid #01411C; margin-bottom: 10pt; }

/* ---- reference line ---------------------------------------------------- */

.refline       { width: 100%; border-collapse: collapse; margin-bottom: 12pt; }
.refline td    { border: 0; padding: 0; font-size: 9.5pt; vertical-align: top; }
.refline .r    { text-align: right; }

/* ---- subject ----------------------------------------------------------- */

/* SUBJECT:-  TEXT, laid out the way official correspondence sets it: the
   label in the left column, the subject underlined in the right. A centred
   subject wraps badly once the title runs long. */
table.subject     { width: 100%; border-collapse: collapse; margin: 0 0 14pt; }
table.subject td  { border: 0; padding: 0; vertical-align: top; font-size: 10.5pt; }
table.subject td.lbl2 { width: 62pt; font-weight: bold; }
table.subject td.txt  { font-weight: bold; letter-spacing: 0.2pt;
                        border-bottom: 0.8pt solid #000000; padding-bottom: 2pt; }

/* ---- headings ---------------------------------------------------------- */

h1 { font-size: 13pt; margin: 0 0 6pt; text-align: center; }
h2 { font-size: 11pt; margin: 14pt 0 6pt; color: #01411C;
     border-bottom: 0.6pt solid #01411C; padding-bottom: 2.5pt;
     page-break-after: avoid; }
h3 { font-size: 10pt; margin: 10pt 0 4pt; font-style: italic; }
p  { margin: 0 0 7pt; text-align: justify; }

/* ---- tables ------------------------------------------------------------ */

/* Auto layout: Dompdf sizes columns from their content well, whereas a fixed
   layout without an explicit width per column distributes them unevenly. */
table.t     { width: 100%; border-collapse: collapse; margin: 0 0 11pt; }
table.t tr  { page-break-inside: avoid; }
table.t thead { display: table-header-group; }   /* repeat headings across pages */
table.t th, table.t td {
    border: 0.5pt solid #6E7C76; padding: 4pt 5pt;
    text-align: left; font-size: 8.5pt;
}
table.t th {
    background-color: #E8F1EB; font-weight: bold; font-size: 8pt;
    text-transform: uppercase; letter-spacing: 0.2pt;
}
table.t td.num, table.t th.num { text-align: right; }
table.t td.c,   table.t th.c   { text-align: center; }
table.t tfoot td { background-color: #F3F7F4; font-weight: bold; }
table.t tr.hi td { background-color: #FBF3DC; }

/* Serial-number column, as every official table here carries one. */
table.t td.sr, table.t th.sr { text-align: center; width: 26pt; }

/* ---- particulars (key / value) ----------------------------------------- */

table.kv       { width: 100%; border-collapse: collapse; margin-bottom: 10pt; }
table.kv td    { border: 0; padding: 2.5pt 0; font-size: 9.5pt; vertical-align: top; }
table.kv td.k  { width: 36%; padding-right: 10pt; }
table.kv td.k:after { content: ""; }
table.kv td.v  { font-weight: bold; }

/* ---- summary boxes ------------------------------------------------------ */

/* Rows are kept whole, but the table itself may break between them —
   otherwise a tall summary block leaves the first page almost empty. */
table.stats    { width: 100%; border-collapse: collapse; margin-bottom: 12pt; }
table.stats tr { page-break-inside: avoid; }
table.stats td { border: 0.5pt solid #6E7C76; padding: 6pt 7pt; width: 25%; vertical-align: top; }
.stats .lbl    { font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.4pt;
                 display: block; margin-bottom: 2pt; }
.stats .val    { font-size: 13pt; font-weight: bold; color: #01411C; display: block; }
.stats .sub    { font-size: 7.5pt; display: block; margin-top: 1pt; }

/* ---- callouts ----------------------------------------------------------- */

.note   { border: 0.5pt solid #6E7C76; border-left: 3pt solid #01411C;
          padding: 6pt 8pt; margin-bottom: 9pt; font-size: 9pt; }
.warn   { border: 0.5pt solid #B08A3A; border-left: 3pt solid #A66B00;
          background-color: #FBF3DC; padding: 6pt 8pt; margin-bottom: 9pt; font-size: 9pt; }
.danger { border: 0.5pt solid #B3261E; border-left: 3pt solid #B3261E;
          background-color: #FAEDEC; padding: 6pt 8pt; margin-bottom: 9pt; font-size: 9pt; }

.clause { font-size: 8pt; font-style: italic; }
.muted  { color: #3A4A44; }
.faint  { color: #55635D; font-size: 8pt; }
.quote  { border: 0.4pt solid #9AA8A2; padding: 6pt 8pt; margin-bottom: 8pt;
          font-size: 9pt; white-space: pre-wrap; text-align: justify; }

.sect      { page-break-inside: avoid; }
/* A heading plus a short table travel together. */
.keep      { page-break-inside: avoid; }
.pagebreak { page-break-after: always; }

/* ---- signature and distribution ---------------------------------------- */

.sigwrap    { width: 100%; border-collapse: collapse; margin-top: 26pt; }
.sigwrap td { border: 0; padding: 0; vertical-align: top; }
.sigblock   { width: 45%; text-align: center; }
.sigline    { border-top: 0.7pt solid #000000; margin-top: 34pt; padding-top: 3pt;
              font-size: 9.5pt; font-weight: bold; }
.sigrole    { font-size: 8.5pt; }

.distro       { margin-top: 22pt; font-size: 9pt; }
.distro .hd   { font-weight: bold; margin-bottom: 3pt; }
.distro ol    { margin: 0; padding-left: 16pt; }
.distro li    { margin-bottom: 1.5pt; }

.endorse { margin-top: 14pt; font-size: 8.5pt; font-style: italic;
           border-top: 0.4pt solid #9AA8A2; padding-top: 6pt; }

/* Page furniture (the rule, reference and page number along the foot) is
   stamped onto the canvas after layout, not placed here: a position:fixed
   block is re-laid by Dompdf on every page and overlaps the body text. */
</style>
</head>
<body>

@php
    $ref = $reference ?? ('ETPB/ROP/' . strtoupper(substr(($reportCode ?? 'RPT'), 0, 6))
           . '/' . $generatedAt->format('Y') . '/' . $generatedAt->format('mdHi'));
@endphp

{{-- ============ Letterhead ============ --}}
<div class="lh">
    <div class="govt">GOVERNMENT OF THE PUNJAB</div>
    <div class="dept">EVACUEE TRUST PROPERTY BOARD</div>
    <div class="scheme">
        Scheme for the Management and Disposal of Urban Evacuee Trust Properties, 1977
        &mdash; Regularization of Possession, Clause 3(ii)
    </div>
    <div class="addr">65-A, Shahrah-e-Quaid-e-Azam, Lahore</div>
</div>

<table class="rule-flag">
    <tr><td class="w">&nbsp;</td><td class="g">&nbsp;</td></tr>
</table>
<div class="rule-thin"></div>

{{-- ============ Reference and date ============ --}}
<table class="refline">
    <tr>
        <td><strong>No.</strong> {{ $ref }}</td>
        <td class="r"><strong>Dated:</strong> {{ $generatedAt->format('d F Y') }}</td>
    </tr>
</table>

{{-- ============ Subject ============ --}}
<table class="subject">
    <tr>
        <td class="lbl2">SUBJECT:&mdash;</td>
        <td class="txt">{{ mb_strtoupper(trim($__env->yieldContent('doc-subject', 'Report'))) }}</td>
    </tr>
</table>

@yield('doc-body')

{{-- ============ Signature ============ --}}
@isset($generatedBy)
    <table class="sigwrap">
        <tr>
            <td>&nbsp;</td>
            <td class="sigblock">
                <div class="sigline">{{ $generatedBy->name }}</div>
                <div class="sigrole">
                    {{ $generatedBy->designation ?: $generatedBy->primaryRole()?->name }}<br>
                    Evacuee Trust Property Board
                </div>
            </td>
        </tr>
    </table>
@endisset

{{-- ============ Distribution ============ --}}
<div class="distro">
    <div class="hd">Copy forwarded for information and necessary action to:&mdash;</div>
    <ol>
        @foreach (($distribution ?? [
            'The Chairman, Evacuee Trust Property Board, Lahore.',
            'The Administrator concerned.',
            'The District Officer concerned.',
            'Office record.',
        ]) as $line)
            <li>{{ $line }}</li>
        @endforeach
    </ol>
</div>

<div class="endorse">
    This report is generated from the case record maintained under the Scheme 1977.
    Rent is assessed under Clause 10 and enhanced at 8% per annum under Clause 11(ii).
    The white band of the national flag, shown in the rule above, stands for Pakistan&rsquo;s
    religious minorities, whose properties this Board holds in trust.
</div>

</body>
</html>
