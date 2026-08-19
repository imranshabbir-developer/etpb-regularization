<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ArrearsController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DueProcessController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApplyController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\CompletionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\OccupancyController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Regularization of Possession — web routes
|--------------------------------------------------------------------------
|
| Route names are referenced by the sidebar partial, which hides any entry
| whose route is not registered, so this file can grow module by module
| without breaking navigation.
|
*/

Route::redirect('/', '/dashboard')->name('home');

// ---- Guest ---------------------------------------------------------------

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'attempt'])
        ->middleware('throttle:10,1')
        ->name('login.attempt');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ---- Authenticated -------------------------------------------------------

Route::middleware(['auth', 'password.change'])->group(function () {

    Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [PasswordController::class, 'update'])->name('password.update');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // ---- Applications ----------------------------------------------------

    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/', [ApplicationController::class, 'index'])
            ->middleware('can.do:applications.view_own,applications.view_district,applications.view_all')
            ->name('index');

        Route::get('/create', [ApplicationController::class, 'create'])
            ->middleware('can.do:applications.create')
            ->name('create');

        Route::post('/', [ApplicationController::class, 'store'])
            ->middleware('can.do:applications.create')
            ->name('store');

        Route::get('/{application}', [ApplicationController::class, 'show'])
            ->middleware('can.do:applications.view_own,applications.view_district,applications.view_all')
            ->name('show');

        Route::post('/{application}/transition', [ApplicationController::class, 'transition'])
            ->name('transition');
    });

    // ---- Area calculator (used by the intake wizard) ----------------------

    Route::post('/tools/area-preview', [ApplicationController::class, 'areaPreview'])
        ->name('tools.area-preview');
});

// ---------------------------------------------------------------------------
// Assessment, due process and arrears — appended as the modules landed.
// ---------------------------------------------------------------------------

Route::middleware(['auth', 'password.change'])->group(function () {

    // ---- Rent assessment, Clause 10 --------------------------------------

    Route::prefix('applications/{application}/assessment')->name('assessment.')->group(function () {
        Route::get('/', [AssessmentController::class, 'show'])
            ->middleware('can.do:assessment.view')->name('show');

        Route::post('/rounds', [AssessmentController::class, 'openRound'])
            ->middleware('can.do:assessment.propose')->name('rounds.store');
    });

    Route::prefix('assessment/rounds/{round}')->name('assessment.')->group(function () {
        Route::post('/rates', [AssessmentController::class, 'storeRateInput'])
            ->middleware('can.do:assessment.rate_inputs')->name('rates.store');

        Route::post('/comparables', [AssessmentController::class, 'storeComparable'])
            ->middleware('can.do:assessment.rate_inputs')->name('comparables.store');

        Route::post('/propose', [AssessmentController::class, 'propose'])
            ->middleware('can.do:assessment.propose')->name('propose');

        Route::post('/determine', [AssessmentController::class, 'determine'])
            ->middleware('can.do:assessment.fix_rent')->name('determine');

        Route::post('/preview', [AssessmentController::class, 'preview'])
            ->middleware('can.do:assessment.view')->name('preview');
    });

    Route::delete('/assessment/rates/{input}', [AssessmentController::class, 'destroyRateInput'])
        ->middleware('can.do:assessment.rate_inputs')->name('assessment.rates.destroy');

    Route::delete('/assessment/comparables/{comparable}', [AssessmentController::class, 'destroyComparable'])
        ->middleware('can.do:assessment.rate_inputs')->name('assessment.comparables.destroy');

    // ---- Notices, objections and hearings, Clause 10(i)(b)-(d) ------------

    Route::prefix('applications/{application}/due-process')->name('due-process.')->group(function () {
        Route::get('/', [DueProcessController::class, 'index'])
            ->middleware('can.do:notices.view')->name('index');

        Route::post('/notices', [DueProcessController::class, 'storeNotice'])
            ->middleware('can.do:notices.issue')->name('notices.store');

        Route::post('/objections', [DueProcessController::class, 'storeObjection'])
            ->middleware('can.do:objections.record')->name('objections.store');

        Route::post('/hearings', [DueProcessController::class, 'storeHearing'])
            ->middleware('can.do:hearings.schedule')->name('hearings.store');
    });

    Route::post('/objections/{objection}/decide', [DueProcessController::class, 'decideObjection'])
        ->middleware('can.do:objections.decide')->name('objections.decide');

    Route::post('/hearings/{hearing}/record', [DueProcessController::class, 'recordHearing'])
        ->middleware('can.do:hearings.record')->name('hearings.record');

    // ---- Arrears, Clause 3(ii)(b), 12 and 13 ------------------------------

    Route::prefix('applications/{application}/arrears')->name('arrears.')->group(function () {
        Route::get('/', [ArrearsController::class, 'index'])
            ->middleware('can.do:arrears.view')->name('index');

        Route::post('/regenerate', [ArrearsController::class, 'regenerate'])
            ->middleware('can.do:arrears.generate')->name('regenerate');

        Route::post('/receipts', [ArrearsController::class, 'storeReceipt'])
            ->middleware('can.do:arrears.receipt')->name('receipts.store');

        Route::post('/instalments', [ArrearsController::class, 'proposeInstalments'])
            ->middleware('can.do:arrears.instalments')->name('instalments.store');

        Route::post('/remissions', [ArrearsController::class, 'proposeRemission'])
            ->middleware('can.do:arrears.view')->name('remissions.store');
    });

    Route::post('/instalment-plans/{plan}/approve', [ArrearsController::class, 'approveInstalments'])
        ->middleware('can.do:arrears.instalments')->name('instalments.approve');

    Route::post('/remissions/{remission}/approve', [ArrearsController::class, 'approveRemission'])
        ->middleware('can.do:arrears.remit')->name('remissions.approve');
});

// ---------------------------------------------------------------------------
// Head 2 — evidence of possession. Head 5 — the Rs. 5,000 deposit.
// ---------------------------------------------------------------------------

Route::middleware(['auth', 'password.change'])->group(function () {

    Route::prefix('applications/{application}/fee')->name('fee.')->group(function () {
        Route::get('/', [FeeController::class, 'index'])
            ->middleware('can.do:fee.view,fee.record')->name('index');
        Route::post('/', [FeeController::class, 'store'])
            ->middleware('can.do:fee.record')->name('store');
    });

    Route::post('/fee-payments/{payment}/confirm', [FeeController::class, 'confirm'])
        ->middleware('can.do:fee.verify')->name('fee.confirm');

    Route::prefix('applications/{application}/documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])
            ->middleware('can.do:documents.view')->name('index');
        Route::post('/', [DocumentController::class, 'store'])
            ->middleware('can.do:documents.upload')->name('store');
    });

    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
        ->middleware('can.do:documents.download,documents.view')->name('documents.download');
    Route::post('/documents/{document}/verify', [DocumentController::class, 'verify'])
        ->middleware('can.do:documents.verify')->name('documents.verify');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])
        ->middleware('can.do:documents.upload')->name('documents.destroy');
});

// ---------------------------------------------------------------------------
// Head 4, Administrator approval, Head 6 reports, work queues, public sign-up.
// ---------------------------------------------------------------------------

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:6,1')->name('register.store');
});

Route::middleware(['auth', 'password.change'])->group(function () {

    // ---- Head 4: occupant offers and litigation --------------------------

    Route::prefix('applications/{application}/occupancy')->name('occupancy.')->group(function () {
        Route::get('/', [OccupancyController::class, 'index'])
            ->middleware('can.do:litigation.view')->name('index');
        Route::post('/offers', [OccupancyController::class, 'storeOffer'])
            ->middleware('can.do:litigation.manage')->name('offers.store');
        Route::post('/litigation', [OccupancyController::class, 'storeLitigation'])
            ->middleware('can.do:litigation.manage')->name('litigation.store');
    });

    Route::post('/occupant-offers/{offer}/decide', [OccupancyController::class, 'decideOffer'])
        ->middleware('can.do:litigation.manage')->name('occupancy.offers.decide');
    Route::post('/litigation/{litigation}', [OccupancyController::class, 'updateLitigation'])
        ->middleware('can.do:litigation.manage')->name('occupancy.litigation.update');

    // ---- Administrator approval, Clause 3(ii)(d) -------------------------

    Route::get('/approvals', [ApprovalController::class, 'queue'])
        ->middleware('can.do:approvals.administrator,approvals.chairman')->name('queue.approvals');
    Route::get('/applications/{application}/approval', [ApprovalController::class, 'show'])
        ->middleware('can.do:approvals.administrator,approvals.chairman')->name('approvals.show');
    Route::post('/applications/{application}/approval', [ApprovalController::class, 'store'])
        ->middleware('can.do:approvals.administrator')->name('approvals.store');

    // ---- Head 6: reports --------------------------------------------------

    // Every report answers to ?format=pdf|docx|xlsx; without it, it renders on screen.
    Route::get('/reports/glimpse', [ReportController::class, 'glimpse'])
        ->middleware('can.do:reports.executive')->name('reports.glimpse');
    Route::get('/reports/executive', [ReportController::class, 'executive'])
        ->middleware('can.do:reports.executive')->name('reports.executive');
    Route::get('/applications/{application}/report', [ReportController::class, 'deep'])
        ->middleware('can.do:reports.deep')->name('reports.deep');
    Route::get('/reports/registers/{register?}', [ReportController::class, 'register'])
        ->middleware('can.do:reports.registers')->defaults('register', 'applications')->name('reports.registers');

    // ---- Work queues -------------------------------------------------------

    // Accounts belongs here too: the first section of this queue is the list of
    // deposits waiting to be confirmed, which is their whole job.
    Route::get('/queues/scrutiny', [QueueController::class, 'scrutiny'])
        ->middleware('can.do:applications.scrutinise,fee.verify')->name('queue.scrutiny');
    Route::get('/queues/assessment', [QueueController::class, 'assessment'])
        ->middleware('can.do:assessment.view')->name('queue.assessment');
    Route::get('/queues/objections', [QueueController::class, 'objections'])
        ->middleware('can.do:objections.record,objections.decide')->name('queue.objections');
    Route::get('/queues/arrears', [QueueController::class, 'arrears'])
        ->middleware('can.do:arrears.view')->name('queue.arrears');
    Route::get('/queues/litigation', [QueueController::class, 'litigation'])
        ->middleware('can.do:litigation.view')->name('queue.litigation');
});

// ---------------------------------------------------------------------------
// Completion: nomination form, tenancy agreement, regularization order.
// ---------------------------------------------------------------------------

Route::middleware(['auth', 'password.change'])->prefix('applications/{application}/completion')
    ->name('completion.')->group(function () {
        Route::get('/', [CompletionController::class, 'index'])
            ->middleware('can.do:nominees.manage,agreements.execute,orders.issue')->name('index');
        Route::post('/nominee', [CompletionController::class, 'storeNominee'])
            ->middleware('can.do:nominees.manage')->name('nominee.store');
        Route::post('/agreement', [CompletionController::class, 'storeAgreement'])
            ->middleware('can.do:agreements.execute')->name('agreement.store');
        Route::post('/order', [CompletionController::class, 'storeOrder'])
            ->middleware('can.do:orders.issue')->name('order.store');
    });

// ---------------------------------------------------------------------------
// Scheme help widget. Available to anyone signed in, including applicants.
// ---------------------------------------------------------------------------

Route::middleware('auth')->group(function () {
    Route::get('/assistant/topics', [AssistantController::class, 'topics'])->name('assistant.topics');
    Route::post('/assistant/ask', [AssistantController::class, 'ask'])
        ->middleware('throttle:60,1')->name('assistant.ask');
});

// ---------------------------------------------------------------------------
// The public applicant's guided form — the six heads as six steps.
// ---------------------------------------------------------------------------

Route::middleware(['auth', 'password.change'])->group(function () {

    Route::get('/apply', [ApplyController::class, 'start'])
        ->middleware('can.do:applications.create')->name('apply.start');
    Route::get('/my-applications', [ApplyController::class, 'mine'])
        ->middleware('can.do:applications.view_own,applications.view_district')->name('apply.mine');

    Route::middleware('can.do:applications.create')->group(function () {
        Route::get('/apply/about-you', [ApplyController::class, 'applicant'])->name('apply.applicant');
        Route::post('/apply/about-you', [ApplyController::class, 'storeApplicant'])->name('apply.applicant.store');

        Route::get('/apply/property', [ApplyController::class, 'property'])->name('apply.property');
        Route::post('/apply/property', [ApplyController::class, 'storeProperty'])->name('apply.property.store');

        Route::get('/apply/possession', [ApplyController::class, 'possession'])->name('apply.possession');
        Route::post('/apply/possession', [ApplyController::class, 'storePossession'])->name('apply.possession.store');

        Route::get('/apply/{application}/evidence', [ApplyController::class, 'evidence'])->name('apply.evidence');
        Route::get('/apply/{application}/occupants', [ApplyController::class, 'occupants'])->name('apply.occupants');
        Route::post('/apply/{application}/occupants', [ApplyController::class, 'storeOccupants'])->name('apply.occupants.store');
        Route::get('/apply/{application}/deposit', [ApplyController::class, 'fee'])->name('apply.fee');
        Route::post('/apply/{application}/submit', [ApplyController::class, 'submit'])->name('apply.submit');
        Route::get('/apply/{application}/done', [ApplyController::class, 'done'])->name('apply.done');
    });
});

// ---------------------------------------------------------------------------
// Administration.
// ---------------------------------------------------------------------------

Route::middleware(['auth', 'password.change'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminController::class, 'users'])
        ->middleware('can.do:users.manage')->name('users');
    Route::post('/users', [AdminController::class, 'storeUser'])
        ->middleware('can.do:users.manage')->name('users.store');
    Route::post('/users/{user}', [AdminController::class, 'updateUser'])
        ->middleware('can.do:users.manage')->name('users.update');

    Route::get('/reference-data', [AdminController::class, 'masters'])
        ->middleware('can.do:masters.manage')->name('masters');
    Route::post('/reference-data/districts/{district}', [AdminController::class, 'updateDistrictProfile'])
        ->middleware('can.do:masters.manage')->name('masters.district');

    Route::get('/settings', [AdminController::class, 'settings'])
        ->middleware('can.do:settings.manage')->name('settings');
    Route::post('/settings', [AdminController::class, 'updateSetting'])
        ->middleware('can.do:settings.manage')->name('settings.update');

    Route::get('/audit', [AdminController::class, 'audit'])
        ->middleware('can.do:audit.view')->name('audit');
});


// ---------------------------------------------------------------------------
// Cascading geography lookups for the intake forms.
// ---------------------------------------------------------------------------

Route::middleware(['auth', 'password.change'])->prefix('lookup')->name('lookup.')->group(function () {
    Route::get('/tehsils', [LookupController::class, 'tehsils'])->name('tehsils');
    Route::get('/mouzas', [LookupController::class, 'mouzas'])->name('mouzas');
});
