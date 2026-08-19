<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\DocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Head 2 — evidence of possession, in certified copy.
 *
 * Clause 3(ii)(c) makes regularization turn on "production of documentary
 * evidence or… court order, as the case may be", so this is the evidentiary
 * spine of the whole application.
 *
 * Files are stored outside the document root and served only through the
 * download action below, which checks authorisation first. Every file is
 * hashed on upload: forged Jamabandis and mutations are a known problem, and a
 * stored hash makes later substitution detectable.
 */
class DocumentController extends Controller
{
    private const MAX_KB = 10240;

    private const ALLOWED = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/tiff'      => 'tif',
    ];

    public function index(Request $request, Application $application): View
    {
        $this->authoriseView($request, $application);

        $types = DocumentType::where('is_active', true)->orderBy('display_order')->get();

        $uploaded = $application->documents()
            ->with('documentType')
            ->orderByDesc('id')
            ->get()
            ->groupBy('document_type_id');

        return view('documents.index', [
            'application' => $application->load(['applicant', 'property']),
            'types'       => $types,
            'uploaded'    => $uploaded,
            'maxKb'       => self::MAX_KB,
        ]);
    }

    public function store(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseView($request, $application);

        $data = $request->validate([
            'document_type_id'  => ['required', 'integer', 'exists:document_types,id'],
            'file'              => ['required', 'file', 'max:' . self::MAX_KB],
            'title'             => ['nullable', 'string', 'max:200'],
            'is_certified_copy' => ['nullable', 'boolean'],
            'issuing_authority' => ['nullable', 'string', 'max:200'],
            'document_date'     => ['nullable', 'date', 'before_or_equal:today'],
            'reference_no'      => ['nullable', 'string', 'max:100'],
            'remarks'           => ['nullable', 'string', 'max:2000'],
        ]);

        $file = $request->file('file');

        // Trust the file's actual contents, not the extension it arrived with.
        $mime = $file->getMimeType();
        if (! isset(self::ALLOWED[$mime])) {
            return back()->withInput()->with('error',
                'Only PDF, JPEG, PNG and TIFF files are accepted. This file appears to be ' . $mime . '.');
        }

        $type = DocumentType::findOrFail($data['document_type_id']);

        if ($type->is_certified_copy_required && ! $request->boolean('is_certified_copy')) {
            return back()->withInput()->with('error', sprintf(
                'A %s must be filed as a certified copy. Confirm the copy is certified, or upload the certified one.',
                $type->name,
            ));
        }

        $hash = hash_file('sha256', $file->getRealPath());

        // The same file uploaded twice against the same head is a mistake, not
        // a second document.
        $duplicate = $application->documents()
            ->where('document_type_id', $type->id)
            ->where('sha256', $hash)
            ->exists();

        if ($duplicate) {
            return back()->with('warning', 'That exact file is already on record against this head.');
        }

        $name = sprintf('%s_%s.%s', $type->code, Str::random(24), self::ALLOWED[$mime]);
        $path = $file->storeAs("evidence/{$application->id}", $name, 'local');

        ApplicationDocument::create([
            'application_id'    => $application->id,
            'document_type_id'  => $type->id,
            'title'             => $data['title'] ?: $type->name,
            'file_path'         => $path,
            'original_filename' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime_type'         => $mime,
            'size_bytes'        => $file->getSize(),
            'sha256'            => $hash,
            'is_certified_copy' => $request->boolean('is_certified_copy'),
            'issuing_authority' => $data['issuing_authority'] ?? null,
            'document_date'     => $data['document_date'] ?? null,
            'reference_no'      => $data['reference_no'] ?? null,
            'remarks'           => $data['remarks'] ?? null,
            'status'            => 'PENDING',
            'uploaded_by'       => $request->user()->id,
        ]);

        return back()->with('status', $type->name . ' uploaded and awaiting verification.');
    }

    /**
     * Stream a document. Never served directly by the web server: the check
     * below is the only thing standing between a stored CNIC or Jamabandi and
     * anyone who guesses a URL.
     */
    public function download(Request $request, ApplicationDocument $document): StreamedResponse
    {
        $this->authoriseView($request, $document->application);

        abort_unless(Storage::disk('local')->exists($document->file_path), 404, 'The stored file is missing.');

        DB::table('audit_logs')->insert([
            'auditable_type' => ApplicationDocument::class,
            'auditable_id'   => $document->id,
            'table_name'     => 'application_documents',
            'event'          => 'downloaded',
            'user_id'        => $request->user()->id,
            'user_name'      => $request->user()->name,
            'user_role'      => $request->user()->primaryRole()?->code,
            'ip_address'     => $request->ip(),
            'user_agent'     => mb_substr((string) $request->userAgent(), 0, 255),
            'url'            => mb_substr($request->fullUrl(), 0, 500),
            'method'         => $request->method(),
            'description'    => 'Downloaded ' . $document->title,
            'created_at'     => now(),
        ]);

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }

    /**
     * Verification by the District Officer. Clause 3(ii)(c) allows a court
     * order to stand in for the ordinary evidence bundle, which is why a
     * mandatory head can be waived — but only for a recorded reason.
     */
    public function verify(Request $request, ApplicationDocument $document): RedirectResponse
    {
        $this->authoriseView($request, $document->application);

        $data = $request->validate([
            'action'  => ['required', Rule::in(['VERIFIED', 'DEFICIENT', 'REJECTED', 'WAIVED'])],
            'remarks' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'remarks.required' => 'Record what was checked, or what is wrong with it.',
        ]);

        if ($data['action'] === 'WAIVED') {
            if (! $document->documentType?->is_waivable) {
                return back()->with('error', sprintf(
                    '%s cannot be waived. It is required in every case.',
                    $document->documentType?->name,
                ));
            }
            if (! $request->user()->hasPermission('documents.waive')) {
                return back()->with('error', 'You do not hold the permission to waive a document.');
            }
        }

        DB::transaction(function () use ($document, $data, $request) {
            $document->update([
                'status'               => $data['action'],
                'verified_by'          => $request->user()->id,
                'verified_at'          => now(),
                'verification_remarks' => $data['remarks'],
            ]);

            DB::table('document_verifications')->insert([
                'application_document_id' => $document->id,
                'action'                  => $data['action'] === 'DEFICIENT' ? 'MARKED_DEFICIENT' : $data['action'],
                'remarks'                 => $data['remarks'],
                'actor_id'                => $request->user()->id,
                'actor_role'              => $request->user()->primaryRole()?->code,
                'acted_at'                => now(),
            ]);
        });

        return back()->with('status', sprintf(
            '%s marked %s.',
            $document->documentType?->name ?? 'Document',
            strtolower($data['action']),
        ));
    }

    public function destroy(Request $request, ApplicationDocument $document): RedirectResponse
    {
        $this->authoriseView($request, $document->application);

        if ($document->status === 'VERIFIED') {
            return back()->with('error',
                'A verified document is part of the record and cannot be removed. Upload a corrected copy instead.');
        }

        $document->delete();   // soft delete — the file itself is retained

        return back()->with('status', 'Document withdrawn.');
    }

    private function authoriseView(Request $request, Application $application): void
    {
        $user = $request->user();

        if ($user->hasPermission('applications.view_all')) {
            return;
        }
        if ($user->hasPermission('applications.view_district')
            && (int) $application->district_id === (int) $user->district_id) {
            return;
        }
        if ($application->applicant?->user_id === $user->id) {
            return;
        }

        abort(403, 'This application is outside your jurisdiction.');
    }
}
