<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ReportExportService;
use App\Services\SchemeAssistantService;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The report exporters and the scheme assistant.
 *
 * The exporter tests check that real files come out — a PDF that begins %PDF
 * and Office packages that begin PK — because a corrupt download looks like a
 * working feature until someone in an office tries to open it.
 *
 * The assistant tests care most about what it does when it does NOT know: a
 * confident wrong answer about eligibility or arrears would cost a member of
 * the public money, so refusing to guess is the behaviour worth pinning.
 */
class ReportAndAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(ReferenceDataSeeder::class);
    }

    // ---- exporters ---------------------------------------------------------

    private function html(): string
    {
        return '<html><body><h1>Test report</h1>'
             . '<table><thead><tr><th>Year</th><th>Rent</th></tr></thead>'
             . '<tbody><tr><td>2006</td><td>1000.00</td></tr></tbody></table>'
             . '</body></html>';
    }

    private function capture(\Symfony\Component\HttpFoundation\StreamedResponse $r): string
    {
        ob_start();
        $r->sendContent();

        return (string) ob_get_clean();
    }

    public function test_pdf_export_produces_a_real_pdf(): void
    {
        $response = app(ReportExportService::class)->pdf($this->html(), 'test-report');

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('test-report.pdf', $response->headers->get('Content-Disposition'));

        $body = $this->capture($response);
        $this->assertStringStartsWith('%PDF', $body);
        $this->assertGreaterThan(1000, strlen($body));
    }

    public function test_word_export_produces_a_real_docx(): void
    {
        $response = app(ReportExportService::class)->word($this->html(), 'test-report', 'Test');

        $this->assertStringContainsString('wordprocessingml', $response->headers->get('Content-Type'));

        // A .docx is a ZIP package; every ZIP begins PK.
        $body = $this->capture($response);
        $this->assertStringStartsWith('PK', $body);
        $this->assertGreaterThan(1000, strlen($body));
    }

    public function test_excel_export_produces_a_real_xlsx(): void
    {
        $response = app(ReportExportService::class)->excel([
            'Summary' => [
                'headings' => ['Measure', 'Value'],
                'rows'     => [['Applications', 12], ['Regularized', 3]],
            ],
        ], 'test-report', 'Test');

        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));

        $body = $this->capture($response);
        $this->assertStringStartsWith('PK', $body);
        $this->assertGreaterThan(1000, strlen($body));
    }

    public function test_export_filenames_are_made_safe(): void
    {
        // Application numbers contain slashes; a filename must not.
        $response = app(ReportExportService::class)
            ->pdf($this->html(), 'ETPB/PB-LAHORE/ROP/2026/0001');

        $disposition = $response->headers->get('Content-Disposition');

        $this->assertStringNotContainsString('/', explode('filename=', $disposition)[1] ?? '');
        $this->assertStringContainsString('.pdf', $disposition);
    }

    public function test_an_unknown_format_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(ReportExportService::class)->render('rtf', $this->html(), [], 'x');
    }

    // ---- official document format -------------------------------------------

    /**
     * The reports go up the chain on paper, so they carry what an official
     * document is expected to carry: the letterhead, a reference number, an
     * underlined subject, a signature block and a distribution list.
     */
    public function test_the_print_layout_carries_the_official_letterhead(): void
    {
        $html = view('layouts.print', [
            'generatedAt' => now(),
            'generatedBy' => $this->officer(),
            'reportCode'  => 'TEST',
        ])->render();

        $this->assertStringContainsString('GOVERNMENT OF THE PUNJAB', $html);
        $this->assertStringContainsString('EVACUEE TRUST PROPERTY BOARD', $html);
        $this->assertStringContainsString('Scheme for the Management and Disposal', $html);
        $this->assertStringContainsString('SUBJECT:', $html);
        $this->assertStringContainsString('Copy forwarded for information', $html);
        $this->assertStringContainsString('ETPB/ROP/TEST/', $html);
    }

    public function test_a_supplied_distribution_list_is_used(): void
    {
        $html = view('layouts.print', [
            'generatedAt'  => now(),
            'generatedBy'  => $this->officer(),
            'reportCode'   => 'TEST',
            'distribution' => ['The Minister-in-charge of the Division concerned.'],
        ])->render();

        $this->assertStringContainsString('The Minister-in-charge of the Division concerned.', $html);
    }

    public function test_the_signature_block_names_the_generating_officer(): void
    {
        $html = view('layouts.print', [
            'generatedAt' => now(),
            'generatedBy' => $this->officer(),
            'reportCode'  => 'TEST',
        ])->render();

        $this->assertStringContainsString('Aslam Khan', $html);
        $this->assertStringContainsString('Deputy Administrator', $html);
    }

    /**
     * Every void element must be self-closed before PhpWord sees it: the reader
     * parses the markup as XML, and one stray <col> aborts the whole export.
     * This was a live defect — the Word download returned an HTML error page.
     */
    public function test_word_export_survives_unclosed_void_elements(): void
    {
        $html = '<html><body><table><colgroup><col style="width:50%"><col style="width:50%"></colgroup>'
              . '<tr><td>A<br>B</td><td>C</td></tr></table><hr></body></html>';

        $response = app(ReportExportService::class)->word($html, 'void-elements', 'Test');
        $body = $this->capture($response);

        $this->assertStringStartsWith('PK', $body);
        $this->assertGreaterThan(1000, strlen($body));
    }

    public function test_pdf_accepts_a_reference_for_the_page_footer(): void
    {
        $response = app(ReportExportService::class)
            ->pdf($this->html(), 'numbered', 'portrait', 'ETPB/ROP/TEST/2026/0001');

        $body = $this->capture($response);

        $this->assertStringStartsWith('%PDF', $body);
        $this->assertGreaterThan(1000, strlen($body));
    }

    private function officer(): User
    {
        $user = new User();
        $user->name = 'Aslam Khan';
        $user->designation = 'Deputy Administrator';

        return $user;
    }

    // ---- the assistant ------------------------------------------------------

    public function test_it_answers_the_eligibility_question_with_the_clause(): void
    {
        $r = app(SchemeAssistantService::class)->ask('am I eligible to apply?');

        $this->assertTrue($r['matched']);
        $this->assertSame('Clause 3(ii)(a)', $r['clause']);
        $this->assertStringContainsString('1 January 2010', $r['answer']);
    }

    public function test_it_gives_the_fee_from_the_settings_table_not_a_hard_coded_figure(): void
    {
        $r = app(SchemeAssistantService::class)->ask('how much is the fee');

        $this->assertTrue($r['matched']);
        $this->assertStringContainsString('5,000', $r['answer']);
        $this->assertStringContainsString('Chairman ETPB', $r['answer']);
    }

    public function test_it_tells_a_late_occupant_the_truth(): void
    {
        $r = app(SchemeAssistantService::class)->ask('I took possession in 2012, can I still apply?');

        $this->assertTrue($r['matched']);
        $this->assertStringContainsString('Unfortunately not', $r['answer']);
    }

    public function test_it_does_not_promise_ownership(): void
    {
        $r = app(SchemeAssistantService::class)->ask('will I become the owner of the property');

        $this->assertTrue($r['matched']);
        $this->assertStringContainsString('not the owner', $r['answer']);
        $this->assertStringContainsString('tenant', $r['answer']);
    }

    public function test_it_explains_arrears_run_from_the_earliest_date(): void
    {
        $r = app(SchemeAssistantService::class)->ask('what are arrears and how much will I owe');

        $this->assertTrue($r['matched']);
        $this->assertSame('Clause 3(ii)(b)', $r['clause']);
        $this->assertStringContainsString('1 July 2000', $r['answer']);
    }

    /**
     * The most important behaviour here. A generative model would invent an
     * answer; this one says it does not know and points to the district office.
     */
    /**
     * A member of the public does not phrase a question the way the knowledge
     * base is written. These are the plain wordings people actually type, and
     * each has to land on the right clause rather than on the fallback.
     */
    public function test_it_understands_how_ordinary_people_phrase_the_question(): void
    {
        $expected = [
            'how much do i have to pay'      => 'Board requirement',
            'will i own the property'        => 'Clause 3(ii)',
            'am i allowed to apply'          => 'Clause 3(ii)(a)',
            'how much rent will i pay'       => 'Clause 10',
            'can i pay in easy instalments'  => 'Clause 13',
            'how much do i owe'              => 'Clause 3(ii)(b)',
        ];

        foreach ($expected as $question => $clause) {
            $r = app(SchemeAssistantService::class)->ask($question);

            $this->assertTrue($r['matched'], "Did not understand: {$question}");
            $this->assertSame($clause, $r['clause'], "Wrong clause for: {$question}");
        }
    }

    public function test_it_refuses_to_guess_when_it_does_not_know(): void
    {
        foreach (['what is the weather today', 'who won the match', 'zzzzz qqqqq'] as $question) {
            $r = app(SchemeAssistantService::class)->ask($question);

            $this->assertFalse($r['matched'], "Wrongly matched: {$question}");
            $this->assertStringContainsString('could not match', $r['answer']);
            $this->assertStringContainsString('district office', $r['answer']);
        }
    }

    public function test_an_empty_question_falls_back_rather_than_erroring(): void
    {
        $r = app(SchemeAssistantService::class)->ask('   ');

        $this->assertFalse($r['matched']);
        $this->assertNotEmpty($r['suggestions']);
    }

    public function test_every_answer_offers_somewhere_to_go_next(): void
    {
        foreach (app(SchemeAssistantService::class)->topics() as $topic) {
            $r = app(SchemeAssistantService::class)->ask($topic);

            $this->assertTrue($r['matched'], "Its own suggested topic did not match: {$topic}");
            $this->assertNotEmpty($r['suggestions']);
        }
    }
}
