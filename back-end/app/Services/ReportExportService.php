<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html as WordHtml;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Renders a report to PDF, Word or Excel.
 *
 * A government office needs the same report three ways: a PDF to file and sign,
 * a Word document to amend before it goes up the chain, and a spreadsheet to
 * sort and total. Rather than maintain three renderers, one Blade view produces
 * self-contained HTML and PDF and Word are generated from it; only Excel is
 * built separately, because a spreadsheet wants rows and columns, not a page.
 *
 * The HTML is deliberately styled with plain CSS rather than the application's
 * Tailwind build: Dompdf and PhpWord both parse a narrow subset of CSS, and a
 * report that renders differently on paper than on screen is worse than no
 * report at all.
 */
class ReportExportService
{
    public const FORMATS = ['pdf' => 'PDF', 'docx' => 'MS Word', 'xlsx' => 'Excel', 'html' => 'Screen'];

    /**
     * Stream a PDF built from report HTML.
     */
    public function pdf(
        string $html,
        string $filename,
        string $orientation = 'portrait',
        string $reference = '',
    ): StreamedResponse {
        $options = new Options();
        $options->set('isRemoteEnabled', false);      // no outbound fetches from a report
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');  // ships with Dompdf; has the glyphs we need
        $options->set('chroot', base_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        // Page furniture is stamped onto the canvas after layout rather than
        // placed with position:fixed. Dompdf re-lays a fixed block on every
        // page and it collides with the flowing content underneath, which
        // produced overlapping text at the foot of each page.
        $this->stampPageFurniture($dompdf, $reference);

        $output = $dompdf->output();

        return response()->streamDownload(
            fn () => print($output),
            $this->safeName($filename, 'pdf'),
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * Stream a Word document built from the same report HTML.
     */
    public function word(string $html, string $filename, string $title = ''): StreamedResponse
    {
        $word = new PhpWord();
        $word->getDocInfo()
            ->setCreator('Evacuee Trust Property Board')
            ->setTitle($title ?: $filename)
            ->setDescription('Regularization of Possession — Scheme 1977');

        $word->setDefaultFontName('Calibri');
        $word->setDefaultFontSize(10);

        $section = $word->addSection([
            'marginTop' => 700, 'marginBottom' => 700,
            'marginLeft' => 700, 'marginRight' => 700,
        ]);

        // PhpWord parses a narrower subset of HTML than Dompdf, so the markup
        // is simplified before it is handed over.
        WordHtml::addHtml($section, $this->simplifyForWord($html), false, false);

        $writer = WordIOFactory::createWriter($word, 'Word2007');

        return response()->streamDownload(
            function () use ($writer) {
                $writer->save('php://output');
            },
            $this->safeName($filename, 'docx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        );
    }

    /**
     * Stream a real .xlsx workbook.
     *
     * @param  array<string, array{headings: array<int,string>, rows: iterable, widths?: array<int,int>}>  $sheets
     */
    public function excel(array $sheets, string $filename, string $title = ''): StreamedResponse
    {
        $book = new Spreadsheet();
        $book->getProperties()
            ->setCreator('Evacuee Trust Property Board')
            ->setTitle($title ?: $filename);

        $book->removeSheetByIndex(0);

        foreach ($sheets as $name => $sheet) {
            $ws = $book->createSheet();
            $ws->setTitle(mb_substr(preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '-', $name), 0, 31));

            $r = 1;
            $headings = $sheet['headings'] ?? [];

            if ($headings !== []) {
                foreach (array_values($headings) as $i => $heading) {
                    $ws->setCellValue([$i + 1, $r], $heading);
                }
                $last = count($headings);
                $ws->getStyle([1, $r, $last, $r])->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '01411C']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCEFE4']],
                    'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
                $ws->freezePane([1, $r + 1]);
                $r++;
            }

            foreach ($sheet['rows'] as $row) {
                $i = 1;
                foreach ((array) $row as $value) {
                    $ws->setCellValue([$i++, $r], $this->cellValue($value));
                }
                $r++;
            }

            $columns = max(1, count($headings));
            for ($c = 1; $c <= $columns; $c++) {
                $ws->getColumnDimensionByColumn($c)->setAutoSize(true);
            }
        }

        if ($book->getSheetCount() === 0) {
            $book->createSheet()->setTitle('Empty');
        }
        $book->setActiveSheetIndex(0);

        $writer = new Xlsx($book);
        $writer->setPreCalculateFormulas(false);

        return response()->streamDownload(
            function () use ($writer) {
                $writer->save('php://output');
            },
            $this->safeName($filename, 'xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /**
     * Turn a Blade-rendered report into whichever format was asked for.
     *
     * @param  array<string, array{headings: array<int,string>, rows: iterable}>  $sheets
     */
    public function render(
        string $format,
        string $html,
        array $sheets,
        string $filename,
        string $title = '',
        string $orientation = 'portrait',
        string $reference = '',
    ): StreamedResponse {
        return match ($format) {
            'pdf'  => $this->pdf($html, $filename, $orientation, $reference),
            'docx' => $this->word($html, $filename, $title),
            'xlsx' => $this->excel($sheets, $filename, $title),
            default => throw new \InvalidArgumentException("Unsupported report format [{$format}]."),
        };
    }


    /**
     * Rule, reference and "Page n of m" along the foot of every page.
     *
     * Written straight to the canvas after rendering because Dompdf recomputes
     * a position:fixed block per page and lays it over the body text.
     */
    private function stampPageFurniture(Dompdf $dompdf, string $reference): void
    {
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Serif', 'normal');

        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $margin = 42;      // matches the 18mm side margin closely enough at 72dpi
        $y = $h - 40;

        $canvas->line($margin, $y - 4, $w - $margin, $y - 4, [0.6, 0.66, 0.63], 0.5);

        if ($reference !== '') {
            $canvas->text($margin, $y, $reference, $font, 7, [0.33, 0.39, 0.36]);
        }

        // PAGE_NUM and PAGE_COUNT are substituted by Dompdf at output time.
        $canvas->page_text(
            $w - $margin - 70, $y, 'Page {PAGE_NUM} of {PAGE_COUNT}',
            $font, 7, [0.33, 0.39, 0.36],
        );
    }

    // ---- helpers ---------------------------------------------------------

    private function cellValue(mixed $value): string|int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        // Keep long identifiers such as a CNIC as text; Excel would otherwise
        // render 3520112349876 in scientific notation.
        if (is_numeric($value) && strlen((string) $value) <= 12 && ! str_starts_with((string) $value, '0')) {
            return str_contains((string) $value, '.') ? (float) $value : (int) $value;
        }

        return (string) $value;
    }

    /**
     * How each class in the print layout renders once inlined for Word.
     *
     * PhpWord reads inline style attributes but ignores a stylesheet entirely,
     * so a Word export built from class-based markup arrives unstyled. Rather
     * than keep two copies of the report, the classes are mapped to the inline
     * equivalents here and applied only on the Word path — the PDF keeps using
     * the stylesheet, where it renders better.
     *
     * @var array<string, string>
     */
    private const WORD_STYLES = [
        'govt'      => 'text-align:center;font-size:12pt;font-weight:bold;color:#01411C;',
        'dept'      => 'text-align:center;font-size:15pt;font-weight:bold;',
        'scheme'    => 'text-align:center;font-size:9pt;font-style:italic;',
        'addr'      => 'text-align:center;font-size:8pt;',
        'lh'        => 'text-align:center;',
        'rule-flag' => 'width:100%;border-bottom:3pt solid #01411C;',
        'w'         => 'width:16pt;background-color:#FFFFFF;',
        'g'         => 'background-color:#01411C;',
        'keep'      => '',
        'rule-thin' => 'border-bottom:1pt solid #01411C;',
        'subject'   => 'width:100%;',
        'lbl2'      => 'font-weight:bold;font-size:10.5pt;width:62pt;',
        'txt'       => 'font-weight:bold;font-size:10.5pt;border-bottom:1pt solid #000000;',
        'refline'   => 'width:100%;',
        'r'         => 'text-align:right;',
        'c'         => 'text-align:center;',
        'num'       => 'text-align:right;',
        'sr'        => 'text-align:center;',
        't'         => 'width:100%;border:1pt solid #6E7C76;',
        'kv'        => 'width:100%;',
        'k'         => 'width:36%;color:#3A4A44;',
        'v'         => 'font-weight:bold;',
        'stats'     => 'width:100%;',
        'lbl'       => 'font-size:8pt;color:#3A4A44;',
        'val'       => 'font-size:13pt;font-weight:bold;color:#01411C;',
        'sub'       => 'font-size:8pt;color:#55635D;',
        'note'      => 'border:1pt solid #6E7C76;background-color:#F1F8F4;font-size:9pt;',
        'warn'      => 'border:1pt solid #B08A3A;background-color:#FBF3DC;font-size:9pt;',
        'danger'    => 'border:1pt solid #B3261E;background-color:#FAEDEC;font-size:9pt;',
        'hi'        => 'background-color:#FBF3DC;',
        'quote'     => 'border:1pt solid #9AA8A2;font-size:9pt;',
        'clause'    => 'font-size:8pt;font-style:italic;',
        'muted'     => 'color:#3A4A44;',
        'faint'     => 'color:#55635D;font-size:8pt;',
        'sigline'   => 'border-top:1pt solid #000000;font-weight:bold;text-align:center;',
        'sigrole'   => 'font-size:8.5pt;text-align:center;',
        'sigblock'  => 'text-align:center;',
        'distro'    => 'font-size:9pt;',
        'hd'        => 'font-weight:bold;',
        'endorse'   => 'font-size:8.5pt;font-style:italic;border-top:1pt solid #9AA8A2;color:#3A4A44;',
    ];

    /**
     * PhpWord's HTML reader chokes on several things Dompdf handles happily.
     * Strip what it cannot read, and inline the styling it would otherwise
     * lose, rather than let it emit an unstyled document.
     */
    private function simplifyForWord(string $html): string
    {
        // Keep only the body.
        if (preg_match('#<body[^>]*>(.*)</body>#si', $html, $m)) {
            $html = $m[1];
        }

        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#si', '', $html) ?? $html;
        $html = preg_replace('#<svg\b[^>]*>.*?</svg>#si', '', $html) ?? $html;

        // Page furniture belongs to the PDF only; in Word it would land mid-document.
        $html = preg_replace('#<div\s+id="pdf-footer".*?</div>#si', '', $html) ?? $html;

        // Turn classes into the inline styles PhpWord can actually read.
        $html = preg_replace_callback(
            '#\sclass="([^"]*)"#i',
            function (array $m): string {
                $declarations = '';
                foreach (preg_split('/\s+/', trim($m[1])) ?: [] as $class) {
                    $declarations .= self::WORD_STYLES[$class] ?? '';
                }

                return $declarations === '' ? '' : ' style="' . $declarations . '"';
            },
            $html,
        ) ?? $html;

        // Merge a pre-existing style attribute with the one just generated.
        $html = preg_replace('#\sstyle="([^"]*)"\s+style="([^"]*)"#i', ' style="$1$2"', $html) ?? $html;

        // colspan and width are kept: PhpWord honours both, and dropping them
        // would collapse every table that spans a total row.
        $html = preg_replace('#\s(id|role|aria-[a-z-]+|title|lang|datetime)="[^"]*"#i', '', $html) ?? $html;
        // PhpWord parses the markup as XML, where every void element must be
        // self-closed. One stray <col> or <br> aborts the whole export.
        $html = preg_replace(
            '#<(br|hr|col|img|input|meta|link)(\s[^>/]*?)?/?>#i',
            '<$1$2 />',
            $html,
        ) ?? $html;

        return trim($html);
    }

    private function safeName(string $filename, string $extension): string
    {
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?: 'report';
        $base = trim($base, '-');

        return mb_substr($base, 0, 120) . '.' . $extension;
    }
}
