<?php
// routes/web.php

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

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/portfolio', [HomeController::class, 'portfolio'])->name('portfolio');
Route::get('/blog', [HomeController::class, 'blog'])->name('blog');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');

// Google Auth
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('login.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);

// Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Routes
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
        
        // Password routes
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
        Route::post('/password/confirm', [ProfileController::class, 'confirmPassword'])->name('password.confirm');
    });

    // Users Resource
    Route::resource('users', UserController::class);

    // Custom User Loan Routes
    Route::prefix('users/{user}')->name('users.')->group(function () {
        Route::get('/loans', [LoanController::class, 'index'])->name('loans');
        Route::get('/loans/create', [LoanController::class, 'createForUser'])->name('loans.create');
        Route::get('/loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
        Route::get('/loans/{loan}/edit', [LoanController::class, 'edit'])->name('loans.edit');
        Route::get('/loans/{loan}/repayments/create', [RepaymentController::class, 'create'])->name('loans.repayments.create');
        Route::post('/loans/{loan}/repayments', [RepaymentController::class, 'store'])->name('loans.repayments.store');
    });

    // Loans Resource
    Route::resource('loans', LoanController::class);
    
    // Custom Loan Routes
    Route::prefix('loans/{loan}')->name('loans.')->group(function () {
        Route::get('/generate-pdf/{loanId}', [LoanController::class, 'generatePdf'])->name('generatePdf');
        Route::get('/agreement/download', [LoanController::class, 'downloadAgreement'])->name('agreement.download');
        Route::get('/agreement/show', [LoanController::class, 'showAgreement'])->name('agreement.show');
        Route::post('/signature', [LoanController::class, 'saveSignature'])->name('signature.save');
        Route::get('/user-loans', [LoanController::class, 'getUserLoans'])->name('user-loans');
        Route::get('/chart-data', [LoanController::class, 'chartData'])->name('chart-data');
    });

    // Admin Loan Edit
    Route::middleware('admin')->get('/admin/loans/{loan}/edit', [LoanController::class, 'adminEdit'])->name('admin.loans.edit');

    // Disbursements Resource
    Route::resource('disbursements', DisbursementController::class);

    // Repayments Resource
    Route::resource('repayments', RepaymentController::class);

    // ============ REPORTS ROUTES ============
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/{reportType}', [ReportController::class, 'show'])->name('show');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
    });

    // ============ INVESTMENT ROUTES ============
    Route::prefix('investments')->name('investments.')->middleware(['auth', 'verified'])->group(function () {
        Route::get('/', [InvestmentController::class, 'index'])->name('index');
        Route::get('/{investment}', [InvestmentController::class, 'show'])->name('show');
        Route::post('/store', [InvestmentController::class, 'store'])->name('store');
        Route::put('/update/{investment}', [InvestmentController::class, 'update'])->name('update');
        Route::delete('/destroy/{investment}', [InvestmentController::class, 'destroy'])->name('destroy');
        Route::post('/{investment}/note', [InvestmentController::class, 'addNote'])->name('note.add');
        Route::post('/{investment}/milestone', [InvestmentController::class, 'addMilestone'])->name('milestone.add');
        Route::post('/{investment}/funding', [InvestmentController::class, 'addFunding'])->name('funding.add');
        Route::get('/data', [InvestmentController::class, 'getData'])->name('data');
        Route::get('/stats', [InvestmentController::class, 'getStats'])->name('stats');
    });

    // ============ PARTNER ROUTES ============
    Route::prefix('partners')->name('partners.')->middleware(['auth', 'verified'])->group(function () {
        Route::get('/', [PartnerController::class, 'index'])->name('index');
        Route::get('/show/{partner}', [PartnerController::class, 'show'])->name('show');
        Route::post('/store', [PartnerController::class, 'store'])->name('store');
        Route::put('/update/{partner}', [PartnerController::class, 'update'])->name('update');
        Route::delete('/destroy/{partner}', [PartnerController::class, 'destroy'])->name('destroy');
        Route::post('/{partner}/contribution', [PartnerController::class, 'addContribution'])->name('contribution.add');
        Route::post('/{partner}/withdraw', [PartnerController::class, 'withdraw'])->name('withdraw');
        Route::post('/{partner}/profit', [PartnerController::class, 'distributeProfit'])->name('profit.distribute');
        Route::get('/data', [PartnerController::class, 'getData'])->name('data');
        Route::get('/stats', [PartnerController::class, 'getStats'])->name('stats');
    });

    // System Settings
    Route::prefix('system')->name('system.')->group(function () {
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