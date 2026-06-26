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
        // PDF generation - keep the original parameter name 'id' for user_id
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