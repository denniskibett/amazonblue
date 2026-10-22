<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\SystemController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrivateFileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\DisbursementController;
use App\Http\Controllers\RepaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\CaseController;

// Public Routes (Aimzone Ventures website)
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/robots.txt', [HomeController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');
Route::get('/{page}', [HomeController::class, 'page'])
    ->whereIn('page', HomeController::PAGES)
    ->name('site.page');
Route::post('/contact', [HomeController::class, 'storeEnquiry'])
    ->middleware('throttle:6,1')
    ->name('enquiry.store');

// ============ BORROWER KYC (individual onboarding) ============
Route::middleware(['auth', 'role:borrower'])->prefix('kyc')->name('kyc.')->controller(HomeController::class)->group(function () {
    Route::get('/', fn () => redirect()->route('kyc.personal'))->name('start');
    Route::get('/personal', 'kycPersonal')->name('personal');
    Route::post('/personal', 'storeKycPersonal')->name('personal.store');
    Route::get('/documents', 'kycDocuments')->name('documents');
    Route::post('/documents', 'storeKycDocuments')->name('documents.store');
    Route::get('/income', 'kycIncome')->name('income');
    Route::post('/income', 'storeKycIncome')->name('income.store');
    Route::get('/contacts', 'kycContacts')->name('contacts');
    Route::post('/contacts', 'storeKycContacts')->name('contacts.store');
    Route::get('/review', 'kycReview')->name('review');
    Route::post('/review', 'submitKyc')->middleware('verified')->name('submit'); // email confirmed first (decision 17)
    Route::get('/submitted', 'kycSubmitted')->name('submitted');
});

// ============ PRIVATE DOCUMENTS (KYC ID images, photo, signature: owner or staff only) ============
Route::get('/files/users/{user}/{document}', [PrivateFileController::class, 'user'])
    ->middleware('auth')
    ->whereIn('document', array_keys(\App\Support\PrivateFiles::USER_DOCUMENTS))
    ->name('files.user');
// company documents: the owner, members with a user account, staff KYC reviewers (Business::isVisibleTo)
Route::get('/files/businesses/{business}/{document}', [PrivateFileController::class, 'business'])
    ->middleware('auth')
    ->whereNumber('document')
    ->name('files.business');

// ============ BUSINESS KYC (borrower adds a business; Concerns\BusinessOnboarding; own businesses only) ============
Route::middleware(['auth', 'role:borrower'])->prefix('account/business')->name('account.business.')
    ->controller(HomeController::class)->group(function () {
        Route::get('/', 'businessIndex')->name('index');
        Route::get('/start', 'businessStart')->name('start');
        Route::get('/new', 'businessCreate')->name('create');
        Route::post('/new', 'businessStore')->name('store');
        Route::get('/{business}', 'businessShow')->whereNumber('business')->name('show');
        Route::get('/{business}/details', 'businessDetails')->name('details');
        Route::post('/{business}/details', 'businessUpdateDetails')->name('details.update');
        Route::get('/{business}/members', 'businessMembers')->name('members');
        Route::post('/{business}/members', 'businessStoreMember')->name('members.store');
        Route::post('/{business}/members/next', 'businessMembersNext')->name('members.next');
        Route::delete('/{business}/members/{member}', 'businessRemoveMember')->name('members.destroy');
        Route::get('/{business}/documents', 'businessDocuments')->name('documents');
        Route::post('/{business}/documents', 'businessStoreDocuments')->name('documents.store');
        Route::get('/{business}/financials', 'businessFinancials')->name('financials');
        Route::post('/{business}/financials', 'businessStoreFinancials')->name('financials.store');
        Route::get('/{business}/review', 'businessReview')->name('review');
        Route::post('/{business}/review', 'businessSubmit')->name('submit');
        Route::get('/{business}/submitted', 'businessSubmitted')->name('submitted');
    });

// ============ CUSTOMER ACCOUNT (borrowers & partners; website design, mobile first) ============
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::controller(HomeController::class)->group(function () {
        Route::get('/', 'account')->name('home');
        Route::get('/profile', 'accountProfile')->name('profile');

        Route::middleware('role:borrower')->group(function () {
            Route::get('/borrow', 'accountBorrow')->name('borrow');
            Route::get('/loans', 'accountLoans')->name('loans');
            Route::get('/loans/{loan}', 'accountLoan')->name('loan');
            Route::get('/loans/{loan}/agreement', 'accountAgreement')->name('agreement');
            Route::get('/loans/{loan}/agreement.pdf', 'accountAgreementFile')->name('agreement.file');

            // repayment journey (Concerns\BorrowerRepayment): own loans only, 404 otherwise
            Route::get('/repay', 'accountRepayStart')->name('repay.start');
            Route::get('/loans/{loan}/repay', 'accountRepay')->name('repay');
            Route::post('/loans/{loan}/repay/confirm', 'accountRepayConfirm')->name('repay.confirm'); // rate-limited in the controller (5 per 10 min per borrower)
            Route::post('/loans/{loan}/repay/stk', 'accountRepayStk')->middleware('throttle:3,5')->name('repay.stk');
            Route::get('/loans/{loan}/repay/stk/{request_id}', 'accountRepayStkStatus')->middleware('throttle:60,1')->name('repay.stk.status');
            Route::get('/loans/{loan}/closed', 'accountLoanClosed')->name('closed');
            Route::get('/loans/{loan}/receipts/{repayment}', 'accountReceipt')->name('receipt');

            // borrower journeys (Concerns\BorrowerJourneys): own loans only, 404 otherwise; requests = audit row + staff email
            Route::get('/loans/{loan}/extension', 'accountExtension')->name('extension');
            Route::post('/loans/{loan}/extension', 'requestExtension')->middleware('throttle:3,10')->name('extension.request');
            Route::get('/loans/{loan}/plan', 'accountPlan')->name('plan');
            Route::post('/loans/{loan}/plan', 'acceptPlan')->middleware('throttle:3,10')->name('plan.accept');
            Route::get('/loans/{loan}/statement', 'accountLoanStatement')->name('loan.statement');
            Route::get('/statement-of-account', 'accountLoanStatement')->name('soa');
            Route::get('/notifications', 'accountNotifications')->name('notifications');
            Route::get('/help', 'accountHelp')->name('help');
            Route::post('/help', 'sendHelp')->middleware('throttle:5,10')->name('help.send');
        });

        // partner self-service (Concerns\PartnerSelfService): own data only; requests = audit row + staff email, no posting
        Route::middleware('role:partner')->group(function () {
            Route::get('/statement', 'partnerStatement')->name('statement');
            Route::get('/statement.csv', 'partnerStatementCsv')->name('partner.statement.csv');
            Route::prefix('partner')->name('partner.')->group(function () {
                Route::get('/', 'partnerHome')->name('home');
                Route::get('/investments', 'partnerInvestments')->name('investments');
                Route::get('/investments/{investment}', 'partnerInvestment')->whereNumber('investment')->name('investment');
                Route::get('/loans', 'partnerLoans')->name('loans');
                Route::get('/loans/{loan}', 'partnerLoan')->name('loan');
                Route::get('/earnings', 'partnerEarnings')->name('earnings');
                Route::get('/add-funds', 'partnerFunds')->name('funds');
                Route::post('/add-funds', 'partnerFundsStore')->middleware('throttle:5,10')->name('funds.store');
                Route::get('/withdraw', 'partnerWithdraw')->name('withdraw');
                Route::post('/withdraw', 'partnerWithdrawStore')->middleware('throttle:5,10')->name('withdraw.store');
            });
        });
    });

    // the application itself (fixed product, limits, verification) lives with the other loan logic
    Route::post('/borrow', [LoanController::class, 'apply'])
        ->middleware(['role:borrower', 'throttle:6,1'])
        ->name('borrow.store');
});

// ============ STAFF DASHBOARD (business processes; customers are sent to /account) ============
Route::middleware(['auth', 'verified', 'staff'])->group(function () {
    
    // Dashboard - All authenticated users
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ============ PROFILE ROUTES ============
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::match(['PUT', 'PATCH'], '/update', [ProfileController::class, 'update'])->name('update');
        Route::delete('/destroy', [ProfileController::class, 'destroy'])->name('destroy');
        Route::post('/signature', [ProfileController::class, 'saveSignature'])->name('signature.save');
        Route::delete('/signature', [ProfileController::class, 'deleteSignature'])->name('signature.delete');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    });
    
    // ============ USER MANAGEMENT ============
    Route::resource('users', UserController::class)
        ->middlewareFor(['index', 'show'], 'permission:view users')
        ->middlewareFor(['create', 'store'], 'permission:create users')
        ->middlewareFor(['edit', 'update'], 'permission:edit users')
        ->middlewareFor('destroy', 'permission:delete users');

    // add a missing photo / ID / signature for a customer (paste, drop or choose a file on the user page)
    Route::post('/users/{user}/documents/{document}', [UserController::class, 'storeDocument'])
        ->whereIn('document', array_keys(\App\Support\PrivateFiles::USER_DOCUMENTS))
        ->name('users.documents.store')
        ->middleware('permission:edit users');

    Route::get('/users/{user}/loans-data', [UserController::class, 'getUserLoansData'])
        ->name('users.loans-data')
        ->middleware('permission:view users');

    // ============ KYC REVIEWS (staff verify borrower KYC) ============
    Route::prefix('kyc-reviews')->name('kyc-reviews.')
        ->controller(\App\Http\Controllers\KycReviewController::class)->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:view users');
            Route::get('/{user}', 'show')->name('show')->middleware('permission:view users');
            Route::post('/{user}/approve', 'approve')->name('approve')->middleware('permission:edit users');
            Route::post('/{user}/reject', 'reject')->name('reject')->middleware('permission:edit users');
            Route::post('/{user}/crop', 'crop')->name('crop')->middleware('permission:edit users');
        });

    // ============ BUSINESS REVIEWS (staff verify business KYC) ============
    Route::prefix('business-reviews')->name('business-reviews.')
        ->controller(\App\Http\Controllers\BusinessReviewController::class)->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:view users');
            Route::get('/{business}', 'show')->name('show')->middleware('permission:view users');
            Route::post('/{business}/approve', 'approve')->name('approve')->middleware('permission:edit users');
            Route::post('/{business}/reject', 'reject')->name('reject')->middleware('permission:edit users');
            Route::post('/{business}/documents/{document}/crop', 'crop')->name('crop')->middleware('permission:edit users');
        });

    // ============ USER LOAN ROUTES ============
    Route::prefix('users/{user}')->name('users.')->group(function () {
        Route::get('/loans', [LoanController::class, 'index'])
            ->name('loans')
            ->middleware('permission:view loans');
        
        Route::get('/loans/create', [LoanController::class, 'createForUser'])
            ->name('loans.create')
            ->middleware('permission:create loans');
        
        Route::get('/loans/{loan}', [LoanController::class, 'show'])
            ->name('loans.show')
            ->middleware('permission:view loans');
        
        Route::get('/loans/{loan}/edit', [LoanController::class, 'edit'])
            ->name('loans.edit')
            ->middleware('permission:edit loans');
        
        Route::get('/loans/{loan}/repayments/create', [RepaymentController::class, 'create'])
            ->name('loans.repayments.create')
            ->middleware('permission:create repayments');
        
        Route::post('/loans/{loan}/repayments', [RepaymentController::class, 'store'])
            ->name('loans.repayments.store')
            ->middleware('permission:create repayments');
    });

    // ============ LOANS ============
    Route::resource('loans', LoanController::class)
        ->middlewareFor(['index', 'show'], 'permission:view loans')
        ->middlewareFor(['create', 'store'], 'permission:create loans')
        ->middlewareFor(['edit', 'update'], 'permission:edit loans')
        ->middlewareFor('destroy', 'permission:delete loans');

    Route::prefix('loans/{loan}')->name('loans.')->group(function () {
        // Rollover Routes
        Route::post('/rollover', [LoanController::class, 'rollover'])
            ->name('rollover')
            ->middleware('permission:rollover loans');
        
        Route::get('/rollover-statement', [LoanController::class, 'rolloverStatement'])
            ->name('rollover.statement')
            ->middleware('permission:view loans');
        
        Route::get('/rollover-preview', [LoanController::class, 'getRolloverPreview'])
            ->name('rollover.preview')
            ->middleware('permission:view loans');
        
        // Payment Plan Routes
        Route::get('/payment-plan-preview', [LoanController::class, 'getPaymentPlanPreview'])
            ->name('payment-plan.preview')
            ->middleware('permission:view loans');
        
        Route::post('/payment-plan', [LoanController::class, 'createPaymentPlan'])
            ->name('payment-plan.create')
            ->middleware('permission:edit loans');
        
        // Cycle Routes
        Route::get('/cycles', [LoanController::class, 'getCycles'])
            ->name('cycles')
            ->middleware('permission:view loans');
        
        // Forbearance Routes
        Route::post('/forbearance', [LoanController::class, 'grantForbearance'])
            ->name('forbearance.grant')
            ->middleware('permission:grant forbearance');
        
        Route::patch('/forbearance/end', [LoanController::class, 'endForbearance'])
            ->name('forbearance.end')
            ->middleware('permission:end forbearance');
        
        // Recovery Routes
        Route::patch('/recovery/start', [LoanController::class, 'startRecovery'])
            ->name('recovery.start')
            ->middleware('permission:edit recovery cases');
        
        // Existing routes
        Route::get('/generate-pdf/{loanId}', [LoanController::class, 'generatePdf'])
            ->name('generatePdf')
            ->middleware('permission:view loans');
        
        Route::get('/agreement/download', [LoanController::class, 'downloadAgreement'])
            ->name('agreement.download')
            ->middleware('permission:view loans');
        
        Route::get('/agreement/show', [LoanController::class, 'showAgreement'])
            ->name('agreement.show')
            ->middleware('permission:view loans');
        
        Route::post('/signature', [LoanController::class, 'saveSignature'])
            ->name('signature.save')
            ->middleware('permission:edit loans');

        // Reject a pending/approved application: an approval decision, so 'approve loans'
        Route::patch('/reject', [LoanController::class, 'reject'])
            ->name('reject')
            ->middleware('permission:approve loans');
    });

    // Removed (no view/JS used them, controller methods never existed):
    // loans.user-loans (getUserLoans), loans.chart-data (chartData), admin.loans.edit (adminEdit -> use loans.edit)

    // ============ DISBURSEMENTS ============
    Route::resource('disbursements', DisbursementController::class)
        ->middlewareFor(['index', 'show'], 'permission:view disbursements')
        ->middlewareFor(['create', 'store'], 'permission:create disbursements')
        ->middlewareFor(['edit', 'update'], 'permission:edit disbursements')
        ->middlewareFor('destroy', 'permission:delete disbursements');

    // ============ REPAYMENTS ============
    Route::resource('repayments', RepaymentController::class)
        ->middlewareFor(['index', 'show'], 'permission:view repayments')
        ->middlewareFor(['create', 'store'], 'permission:create repayments')
        ->middlewareFor(['edit', 'update'], 'permission:edit repayments')
        ->middlewareFor('destroy', 'permission:delete repayments');

    // ============ RECOVERY CASES ============
    // Static cases/* paths first, so cases/{case} (show) does not swallow them.
    Route::get('cases/my', [CaseController::class, 'myCases'])
        ->name('cases.my')
        ->middleware('permission:view recovery cases');

    Route::get('cases/stats', [CaseController::class, 'getStats'])
        ->name('cases.stats')
        ->middleware('permission:view recovery cases');

    Route::resource('cases', CaseController::class)
        ->middlewareFor(['index', 'show'], 'permission:view recovery cases')
        ->middlewareFor(['create', 'store'], 'permission:create recovery cases')
        ->middlewareFor(['edit', 'update'], 'permission:edit recovery cases')
        ->middlewareFor('destroy', 'permission:delete recovery cases');

    Route::patch('cases/{case}/recover', [CaseController::class, 'markAsRecovered'])
        ->name('cases.recover')
        ->middleware('permission:edit recovery cases');

    Route::patch('cases/{case}/write-off', [CaseController::class, 'markAsWrittenOff'])
        ->name('cases.write-off')
        ->middleware('permission:edit recovery cases');

    Route::post('cases/{case}/action', [CaseController::class, 'addAction'])
        ->name('cases.action.add')
        ->middleware('permission:edit recovery cases');

    Route::get('cases/{case}/data', [CaseController::class, 'getCaseData'])
        ->name('cases.data')
        ->middleware('permission:view recovery cases');

    Route::get('cases/export/csv', [CaseController::class, 'export'])
        ->name('cases.export')
        ->middleware('permission:view recovery cases');

    // ============ REPORTS ============
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])
            ->name('index')
            ->middleware('permission:view reports');
        
        // before /{reportType}, which would otherwise catch "export"
        Route::get('/export', [ReportController::class, 'export'])
            ->name('export')
            ->middleware('permission:export reports');

        Route::get('/{reportType}', [ReportController::class, 'show'])
            ->name('show')
            ->middleware('permission:view reports');
    });

    // ============ INVESTMENTS ============
    Route::prefix('investments')->name('investments.')->middleware(['auth', 'verified'])->group(function () {
        Route::get('/', [InvestmentController::class, 'index'])
            ->name('index')
            ->middleware('permission:view investments');

        // ============ STATIC ROUTES FIRST (before /{investment}) ============
        Route::get('/data', [InvestmentController::class, 'getData'])
            ->name('data')
            ->middleware('permission:view investments');

        Route::get('/stats', [InvestmentController::class, 'getStats'])
            ->name('stats')
            ->middleware('permission:view investments');

        // ============ THEN the catch-all dynamic route ============
        Route::get('/{investment}', [InvestmentController::class, 'show'])
            ->name('show')
            ->middleware('permission:view investments');

        Route::post('/store', [InvestmentController::class, 'store'])
            ->name('store')
            ->middleware('permission:create investments');

        Route::put('/update/{investment}', [InvestmentController::class, 'update'])
            ->name('update')
            ->middleware('permission:edit investments');

        Route::delete('/destroy/{investment}', [InvestmentController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:delete investments');

        Route::post('/{investment}/note', [InvestmentController::class, 'addNote'])
            ->name('note.add')
            ->middleware('permission:edit investments');

        Route::post('/{investment}/milestone', [InvestmentController::class, 'addMilestone'])
            ->name('milestone.add')
            ->middleware('permission:edit investments');

        Route::post('/{investment}/funding', [InvestmentController::class, 'addFunding'])
            ->name('funding.add')
            ->middleware('permission:edit investments');
    });

    // ============ PARTNERS ============
    Route::prefix('partners')->name('partners.')->middleware(['auth', 'verified'])->group(function () {
        Route::get('/', [PartnerController::class, 'index'])
            ->name('index')
            ->middleware('permission:view partners');

        // ============ STATIC ROUTES FIRST ============
        Route::get('/data', [PartnerController::class, 'getData'])
            ->name('data')
            ->middleware('permission:view partners');

        Route::get('/stats', [PartnerController::class, 'getStats'])
            ->name('stats')
            ->middleware('permission:view partners');

        // ============ THEN dynamic ============
        Route::get('/{partner}', [PartnerController::class, 'show'])
            ->name('show')
            ->middleware('permission:view partners');

        Route::post('/store', [PartnerController::class, 'store'])
            ->name('store')
            ->middleware('permission:create partners');

        Route::put('/update/{partner}', [PartnerController::class, 'update'])
            ->name('update')
            ->middleware('permission:edit partners');

        Route::delete('/destroy/{partner}', [PartnerController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:delete partners');

        Route::post('/{partner}/contribution', [PartnerController::class, 'addContribution'])
            ->name('contribution.add')
            ->middleware('permission:edit partners');

        Route::post('/{partner}/withdraw', [PartnerController::class, 'withdraw'])
            ->name('withdraw')
            ->middleware('permission:edit partners');

        Route::post('/{partner}/profit', [PartnerController::class, 'distributeProfit'])
            ->name('profit.distribute')
            ->middleware('permission:edit partners');
    });

    // ============ SYSTEM SETTINGS ============
    Route::prefix('system')->name('system.')->middleware(['permission:access system settings'])->group(function () { // super-admin only (sysadmin, owner 20 Oct 2026)
        Route::get('/', [SystemController::class, 'index'])->name('index');
        Route::put('/update', [SystemController::class, 'update'])->name('update');
        // where partners send money: super-admin only (+ current password, audit row, admin email)
        Route::put('/partner-funding', [SystemController::class, 'updatePartnerFunding'])
            ->name('partner-funding.update')->middleware('role:super-admin');
        Route::post('/clear-cache', [SystemController::class, 'clearCache'])->name('clear-cache');
        // every branded email with sample values (App\Support\Emails::samples)
        Route::get('/emails', fn () => view('emails.preview', ['samples' => \App\Support\Emails::samples()]))->name('emails');
        Route::get('/emails/{n}', function (int $n) {
            $m = array_values(\App\Support\Emails::samples())[$n] ?? abort(404);

            return view('emails.branded', ['m' => $m]);
        })->whereNumber('n')->name('emails.show');
        Route::get('/backup', [SystemController::class, 'backupDatabase'])->name('backup');
        Route::post('/toggle-maintenance', [SystemController::class, 'toggleMaintenance'])->name('toggle-maintenance');
        // system.debug removed (no method); the unused admin/system/index.blade.php was deleted in Stage 4 Phase 0.
    });

    // ============ STAGE 2 STAFF DESK (docs/stage2/walkthroughs/staff.md) ============
    // Read-only screens and forms; every money/status action posts to the existing routes
    // (loans.update, loans.reject, disbursements.store, repayments.store, cases.*).
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('inbox', [\App\Http\Controllers\Staff\LoanDeskController::class, 'inbox'])->name('inbox');
        Route::get('approvals', [\App\Http\Controllers\Staff\LoanDeskController::class, 'approvals'])
            ->name('approvals')->middleware('permission:view loans');
        // the person side panel, opened from any name (owner, 22 Oct 2026)
        Route::get('users/{user}/peek', \App\Http\Controllers\Staff\UserPeekController::class)
            ->name('users.peek')->middleware('permission:view users');
        // notifications for every staff role (owner, 22 Oct 2026)
        Route::get('notifications', [\App\Http\Controllers\Staff\NotificationsController::class, 'index'])->name('notifications');
        Route::get('notifications/feed', [\App\Http\Controllers\Staff\NotificationsController::class, 'feed'])->name('notifications.feed');
        Route::post('notifications/read', [\App\Http\Controllers\Staff\NotificationsController::class, 'read'])->name('notifications.read');
        Route::get('disbursements/queue', [\App\Http\Controllers\Staff\LoanDeskController::class, 'disbursementQueue'])
            ->name('disbursements.queue')->middleware('permission:view disbursements');
        Route::get('disbursements/{loan}/disburse', [\App\Http\Controllers\Staff\LoanDeskController::class, 'disburseForm'])
            ->name('disbursements.disburse')->middleware('permission:create disbursements');
        Route::get('repayments/receive', [\App\Http\Controllers\Staff\CashDeskController::class, 'receive'])
            ->name('repayments.receive')->middleware('permission:create repayments|delete repayments');
        Route::get('repayments/split-preview', \App\Http\Controllers\Staff\SplitPreviewController::class)
            ->name('repayments.split-preview')->middleware('permission:create repayments|delete repayments');
        // second sign-offs (Stage 4 Phase 5); each decision checks the action's own permission
        Route::get('sign-offs', [\App\Http\Controllers\Staff\SignOffsController::class, 'index'])
            ->name('sign-offs')->middleware('permission:approve loans|delete repayments|write off loan|edit partners');
        Route::post('sign-offs/{approval}', [\App\Http\Controllers\Staff\SignOffsController::class, 'decide'])
            ->name('sign-offs.decide')->middleware('permission:approve loans|delete repayments|write off loan|edit partners');

        // portfolio on the ledger (Stage 4 Phase 7)
        Route::get('portfolio', [\App\Http\Controllers\Staff\PortfolioController::class, 'index'])->name('portfolio')->middleware('permission:view reports');
        Route::get('portfolio.csv', [\App\Http\Controllers\Staff\PortfolioController::class, 'csv'])->name('portfolio.csv')->middleware('permission:view reports');

        // borrower and partner requests (Stage 4 Phase 5)
        Route::get('requests', [\App\Http\Controllers\Staff\RequestsController::class, 'index'])
            ->name('requests')->middleware('permission:rollover loans|edit partners');
        Route::get('requests/borrower/{id}/preview', [\App\Http\Controllers\Staff\RequestsController::class, 'preview'])
            ->name('requests.preview')->middleware('permission:rollover loans');
        Route::post('requests/borrower/{id}', [\App\Http\Controllers\Staff\RequestsController::class, 'borrower'])
            ->name('requests.borrower')->middleware('permission:rollover loans');
        Route::post('requests/partner/{id}', [\App\Http\Controllers\Staff\RequestsController::class, 'partner'])
            ->name('requests.partner')->middleware('permission:edit partners');

        // M-Pesa payments queue (Stage 4 Phase 4)
        Route::get('payments', [\App\Http\Controllers\Staff\PaymentsController::class, 'index'])
            ->name('payments')->middleware('permission:create repayments');
        Route::post('payments/{transaction}/assign', [\App\Http\Controllers\Staff\PaymentsController::class, 'assign'])
            ->name('payments.assign')->middleware('permission:create repayments');
        Route::post('payments/claims/{claim}', [\App\Http\Controllers\Staff\PaymentsController::class, 'decideClaim'])
            ->name('payments.claim')->middleware('permission:create repayments');
        Route::post('payments/reconcile', [\App\Http\Controllers\Staff\PaymentsController::class, 'reconcile'])
            ->name('payments.reconcile')->middleware('permission:create repayments|view reports');
        Route::post('repayments/{repayment}/reverse', [\App\Http\Controllers\Staff\CashDeskController::class, 'reverse'])
            ->name('repayments.reverse')->middleware('permission:delete repayments');
        Route::post('end-of-day/close', [\App\Http\Controllers\Staff\CashDeskController::class, 'closeDay'])
            ->name('end-of-day.close')->middleware('permission:create repayments|create disbursements');
        Route::post('end-of-day/{close}/approve', [\App\Http\Controllers\Staff\CashDeskController::class, 'approveDay'])
            ->name('end-of-day.approve')->middleware('permission:view reports');
        Route::get('end-of-day', [\App\Http\Controllers\Staff\CashDeskController::class, 'endOfDay'])
            ->name('end-of-day')->middleware('permission:create repayments|create disbursements|view reports');
        Route::get('collections', [\App\Http\Controllers\Staff\CollectionsController::class, 'index'])
            ->name('collections')->middleware('permission:view recovery cases');
        Route::get('roles', [\App\Http\Controllers\Staff\SetupController::class, 'roles'])
            ->name('roles')->middleware('permission:manage roles|view audit log');
        Route::post('roles/{role}/permissions', [\App\Http\Controllers\Staff\SetupController::class, 'updateRolePermission'])
            ->name('roles.permissions')->middleware('permission:manage roles');
        Route::post('products/{loanType}', [\App\Http\Controllers\Staff\SetupController::class, 'updateProduct'])
            ->name('products.update')->middleware('permission:manage loan types');
        Route::get('products', [\App\Http\Controllers\Staff\SetupController::class, 'products'])
            ->name('products')->middleware('permission:manage loan types|view reports');
        Route::get('wallets', [\App\Http\Controllers\Staff\FinanceController::class, 'wallets'])
            ->name('wallets')->middleware('permission:reconcile wallets|view house reconciliation');
        Route::get('operations-review', [\App\Http\Controllers\Staff\OperationsReviewController::class, 'index'])
            ->name('operations-review')->middleware('permission:view logs|view ledger');
        Route::post('operations-review/loans/{loan}/forbearance', [\App\Http\Controllers\Staff\OperationsReviewController::class, 'grantForbearance'])
            ->name('operations-review.forbearance')->middleware('permission:grant forbearance');
        Route::post('operations-review/loans/{loan}/due-date', [\App\Http\Controllers\Staff\OperationsReviewController::class, 'correctDueDate'])
            ->name('operations-review.due-date')->middleware('permission:edit loans|manage loan cycles');
        Route::post('operations-review/repairs', [\App\Http\Controllers\Staff\OperationsReviewController::class, 'repairBatch'])
            ->name('operations-review.repair-batch')->middleware('permission:edit loans|manage loan cycles');
        Route::get('operations-review/loans/{loan}/detail', [\App\Http\Controllers\Staff\OperationsReviewController::class, 'repairDetail'])
            ->name('operations-review.detail')->middleware('permission:view ledger');
        Route::post('operations-review/repairs/{loan}', [\App\Http\Controllers\Staff\OperationsReviewController::class, 'repair'])
            ->name('operations-review.repair')->middleware('permission:edit loans|manage loan cycles');
        Route::post('operations-review/rollovers/clean', [\App\Http\Controllers\Staff\OperationsReviewController::class, 'cleanRollovers'])
            ->name('operations-review.rollovers-clean')->middleware('permission:edit loans|manage loan cycles');
        Route::post('operations-review/rollovers/{loan}', [\App\Http\Controllers\Staff\OperationsReviewController::class, 'applyChain'])
            ->name('operations-review.chain')->middleware('permission:edit loans|manage loan cycles');
        Route::post('operations-review/grace', [\App\Http\Controllers\Staff\OperationsReviewController::class, 'applyGraceBatch'])
            ->name('operations-review.grace-batch')->middleware('permission:edit loans|manage loan cycles');
        Route::post('operations-review/grace/{loan}', [\App\Http\Controllers\Staff\OperationsReviewController::class, 'applyGrace'])
            ->name('operations-review.grace')->middleware('permission:edit loans|manage loan cycles|grant forbearance');
        Route::get('partner-payouts', [\App\Http\Controllers\Staff\FinanceController::class, 'partnerPayouts'])
            ->name('partner-payouts')->middleware('permission:view partner statements');
    });

    // ============ AUDIT LOG (read-only) ============
    Route::get('audit-log', [\App\Http\Controllers\AuditLogController::class, 'index'])
        ->name('audit-log.index')
        ->middleware('permission:view audit log');

    // TailAdmin demo pages (calendar, charts, buttons, …) removed in Stage 4 Phase 0: no links, open to any staff.
});

// ============ STAGE 2 COMPONENT GALLERIES (local only; docs/stage2/README.md) ============
if (app()->environment('local')) {
    Route::middleware('auth')->get('/_components', fn () => view('stage2.components-customer'))->name('stage2.components');
    Route::middleware(['auth', 'staff'])->get('/staff/_components', fn () => view('stage2.components-staff'))->name('stage2.components.staff');
}

// Auth Routes
require __DIR__.'/auth.php';

// ============ M-PESA (Daraja) CALLBACKS: Stage 4 Phase 4 ============
// no session or CSRF (bootstrap/app.php); a secret path segment and an optional IP allowlist instead
Route::prefix('mpesa/{secret}')->middleware('throttle:120,1')->name('mpesa.')->group(function () {
    Route::post('c2b/validation', [\App\Http\Controllers\MpesaCallbackController::class, 'validation'])->name('c2b.validation');
    Route::post('c2b/confirmation', [\App\Http\Controllers\MpesaCallbackController::class, 'confirmation'])->name('c2b.confirmation');
    Route::post('stk', [\App\Http\Controllers\MpesaCallbackController::class, 'stk'])->name('stk');
});
