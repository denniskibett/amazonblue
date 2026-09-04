<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\SystemController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleController;
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

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/portfolio', [HomeController::class, 'portfolio'])->name('portfolio');
Route::get('/blog', [HomeController::class, 'blog'])->name('blog');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');

// Google Auth
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('login.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);

// ============ AUTHENTICATED ROUTES ============
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard - All authenticated users
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ============ PROFILE ROUTES ============
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::match(['PUT', 'PATCH'], '/update', [ProfileController::class, 'update'])->name('update');
        Route::delete('/destroy', [ProfileController::class, 'destroy'])->name('destroy');
        Route::delete('/avatar', [ProfileController::class, 'deleteAvatar'])->name('delete-avatar');
        Route::match(['PUT', 'PATCH'], '/address', [ProfileController::class, 'updateAddress'])->name('address.update');
        Route::get('/data', [ProfileController::class, 'getUserData'])->name('data');
        Route::post('/signature', [ProfileController::class, 'saveSignature'])->name('signature.save');
        Route::delete('/signature', [ProfileController::class, 'deleteSignature'])->name('signature.delete');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
        Route::post('/password/confirm', [ProfileController::class, 'confirmPassword'])->name('password.confirm');
    });
    
    // ============ USER MANAGEMENT ============
    Route::resource('users', UserController::class)
        ->middleware('permission:view users|create users|edit users|delete users');

    Route::get('/users/{user}/loans-data', [UserController::class, 'getUserLoansData'])
        ->name('users.loans-data')
        ->middleware('permission:view users');

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
        ->middleware('permission:view loans|create loans|edit loans|delete loans');

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
        
        Route::get('/user-loans', [LoanController::class, 'getUserLoans'])
            ->name('user-loans')
            ->middleware('permission:view loans');
        
        Route::get('/chart-data', [LoanController::class, 'chartData'])
            ->name('chart-data')
            ->middleware('permission:view dashboard');
    });

    // Admin Loan Edit
    Route::middleware('permission:edit loans')
        ->get('/admin/loans/{loan}/edit', [LoanController::class, 'adminEdit'])
        ->name('admin.loans.edit');

    // ============ DISBURSEMENTS ============
    Route::resource('disbursements', DisbursementController::class)
        ->middleware('permission:view disbursements|create disbursements|edit disbursements|delete disbursements');

    // ============ REPAYMENTS ============
    Route::resource('repayments', RepaymentController::class)
        ->middleware('permission:view repayments|create repayments|edit repayments|delete repayments');

    // ============ RECOVERY CASES ============
    Route::resource('cases', CaseController::class)
        ->middleware('permission:view recovery cases|create recovery cases|edit recovery cases|delete recovery cases');

    Route::get('cases/my', [CaseController::class, 'myCases'])
        ->name('cases.my')
        ->middleware('permission:view recovery cases');

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

    Route::get('cases/stats', [CaseController::class, 'getStats'])
        ->name('cases.stats')
        ->middleware('permission:view recovery cases');

    // ============ REPORTS ============
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])
            ->name('index')
            ->middleware('permission:view reports');
        
        Route::get('/{reportType}', [ReportController::class, 'show'])
            ->name('show')
            ->middleware('permission:view reports');
        
        Route::get('/export', [ReportController::class, 'export'])
            ->name('export')
            ->middleware('permission:export reports');
    });

    // ============ INVESTMENTS ============
    Route::prefix('investments')->name('investments.')->middleware(['auth', 'verified'])->group(function () {
        Route::get('/', [InvestmentController::class, 'index'])
            ->name('index')
            ->middleware('permission:view investments');
        
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
        
        Route::get('/data', [InvestmentController::class, 'getData'])
            ->name('data')
            ->middleware('permission:view investments');
        
        Route::get('/stats', [InvestmentController::class, 'getStats'])
            ->name('stats')
            ->middleware('permission:view investments');
    });

    // ============ PARTNERS ============
    Route::prefix('partners')->name('partners.')->middleware(['auth', 'verified'])->group(function () {
        Route::get('/', [PartnerController::class, 'index'])
            ->name('index')
            ->middleware('permission:view partners');
        
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
        
        Route::get('/data', [PartnerController::class, 'getData'])
            ->name('data')
            ->middleware('permission:view partners');
        
        Route::get('/stats', [PartnerController::class, 'getStats'])
            ->name('stats')
            ->middleware('permission:view partners');
    });

    // ============ SYSTEM SETTINGS ============
    Route::prefix('system')->name('system.')->middleware(['permission:access system settings'])->group(function () {
        Route::get('/', [SystemController::class, 'index'])->name('index');
        Route::put('/update', [SystemController::class, 'update'])->name('update');
        Route::get('/clear-cache', [SystemController::class, 'clearCache'])->name('clear-cache');
        Route::get('/backup', [SystemController::class, 'backupDatabase'])->name('backup');
        Route::post('/toggle-maintenance', [SystemController::class, 'toggleMaintenance'])->name('toggle-maintenance');
        Route::post('/debug', [SystemController::class, 'debug'])->name('debug');
    });

    // Static Pages
    $staticPages = [
        'index' => 'index',
        'invoice' => 'invoice',
        '404' => '404',
        'messages' => 'messages',
        'alerts' => 'alerts',
        'blank' => 'blank',
        'calendar' => 'calendar',
        'form-elements' => 'form-elements',
        'basic-tables' => 'basic-tables',
        'avatars' => 'avatars',
        'badge' => 'badge',
        'buttons' => 'buttons',
        'images' => 'images',
        'videos' => 'videos',
        'signin' => 'signin',
        'signup' => 'signup',
        'image' => 'image',
        'line-chart' => 'line-chart',
        'bar-chart' => 'bar-chart',
        'dash' => 'dash',
    ];

    foreach ($staticPages as $path => $view) {
        Route::get('/' . $path, function () use ($view) {
            return view($view);
        })->name($view);
    }
});

// Auth Routes
require __DIR__.'/auth.php';