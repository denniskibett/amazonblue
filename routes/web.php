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
use App\Http\Controllers\DocumentsController;
use App\Http\Controllers\CommissionsController;
use App\Http\Controllers\ReportsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/portfolio', [HomeController::class, 'portfolio'])->name('portfolio');
Route::get('/blog', [HomeController::class, 'blog'])->name('blog');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

     // Users
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');   
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    //Users - Loans
    Route::get('/users/{user}/loans/', [LoanController::class, 'index'])
    ->name('users.loans');
    Route::get('/users/{user}/loans/{loan}', [LoanController::class, 'show'])
    ->name('users.loans.show');
    Route::get('/users/{user}/loans/{loan}/edit', [LoanController::class, 'edit'])
    ->name('users.loans.edit');

    //Users - repayments
    Route::get('/users/{user}/loans/{loan}/repayments/create', [RepaymentController::class, 'create'])
    ->name('users.loans.repayments.create');
    Route::post('/users/{user}/loans/{loan}/repayments', [RepaymentController::class, 'store'])
    ->name('users.loans.repayments.store');


    //PDF
    Route::get('/loans/{id}/generate-pdf/{loanId}', [LoanController::class, 'generatePdf'])
    ->name('loans.generatePdf');


    // Loans
    Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
    Route::get('/loans/create', [LoanController::class, 'create'])->name('loans.create');
    Route::get('/users/{user}/loans/create', [LoanController::class, 'createForUser'])->name('users.loans.create');
    Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::get('/loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
    Route::get('/loans/{loan}/edit', [LoanController::class, 'edit'])->name('loans.edit');
    // Regular loan edit (for borrowers)
    Route::get('/loans/{loan}/edit', [LoanController::class, 'edit'])->name('loans.edit'); 
    // Admin loan edit (if needed)
    Route::middleware('admin')->get('/admin/loans/{loan}/edit', [LoanController::class, 'adminEdit'])->name('admin.loans.edit');
    Route::put('/loans/{loan}', [LoanController::class, 'update'])->name('loans.update');
    Route::delete('/loans/{loan}', [LoanController::class, 'destroy'])->name('loans.destroy');
    
    Route::get('/loans/{loan}/user-loans', [LoanController::class, 'getUserLoans']);
    
    // Loan agreement routes
    Route::get('/loans/{loan}/agreement/download', [LoanController::class, 'downloadAgreement'])->name('loans.agreement.download');
    Route::get('/loans/{loan}/agreement/show', [LoanController::class, 'showAgreement'])->name('loans.agreement.show');
    Route::post('/loans/{loan}/signature', [LoanController::class, 'saveSignature'])->name('loans.signature.save');

    // Profile signature routes
    Route::post('/profile/signature', [ProfileController::class, 'saveSignature'])->name('profile.signature.save');
    Route::delete('/profile/signature', [ProfileController::class, 'deleteSignature'])->name('profile.signature.delete');

    // Disbursements
    Route::get('/disbursements', [DisbursementController::class, 'index'])->name('disbursements.index');
    Route::get('/disbursements/create', [DisbursementController::class, 'create'])->name('disbursements.create');
    Route::post('/disbursements/{loan:id}', [DisbursementController::class, 'store'])->name('disbursements.store');
    Route::get('/disbursements/{disbursement}', [DisbursementController::class, 'show'])->name('disbursements.show');
    Route::get('/disbursements/{disbursement}/edit', [DisbursementController::class, 'edit'])->name('disbursements.edit');
    Route::put('/disbursements/{disbursement}', [DisbursementController::class, 'update'])->name('disbursements.update');
    Route::delete('/disbursements/{disbursement}', [DisbursementController::class, 'destroy'])->name('disbursements.destroy');

    // Repayments
    Route::get('/repayments', [RepaymentController::class, 'index'])->name('repayments.index');
    Route::get('/repayments/create', [RepaymentController::class, 'create'])->name('repayments.create');
    Route::post('/repayments', [RepaymentController::class, 'store'])->name('repayments.store');
    Route::get('/repayments/{repayment}', [RepaymentController::class, 'show'])->name('repayments.show');
    Route::get('/repayments/{repayment}/edit', [RepaymentController::class, 'edit'])->name('repayments.edit');
    Route::put('/repayments/{repayment}', [RepaymentController::class, 'update'])->name('repayments.update');
    Route::delete('/repayments/{repayment}', [RepaymentController::class, 'destroy'])->name('repayments.destroy');

    // Documents
    Route::resource('documents', DocumentsController::class);
    Route::resource('commissions', CommissionsController::class);
    Route::resource('reports', ReportsController::class);
    

    // Chart
    Route::get('/loans/chart-data', [LoanController::class, 'chartData']);




    Route::get('/index', function () {
        return view('index');
    })->name('index');    
    Route::get('/invoice', function () {
        return view('invoice');
    })->name('invoice');
    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');
    Route::get('/404', function () {
        return view('404');
    })->name('404');
    Route::get('/messages', function () {
        return view('messages');
    })->name('messages');
    Route::get('/alerts', function () {
        return view('alerts');
    })->name('alerts');
    Route::get('/blank', function () {
        return view('blank');
    })->name('blank');
    Route::get('/calendar', function () {
        return view('calendar');
    })->name('calendar');
    Route::get('/form-elements', function () {
        return view('form-elements');
    })->name('form-elements');
    Route::get('/basic-tables', function () {
        return view('basic-tables');
    })->name('basic-tables');
    Route::get('/avatars', function () {
        return view('avatars');
    })->name('avatars');
    Route::get('/badge', function () {
        return view('badge');
    })->name('badge');
    Route::get('/buttons', function () {
        return view('buttons');
    })->name('buttons');
    Route::get('/images', function () {
        return view('images');
    })->name('images');
    Route::get('/videos', function () {
        return view('videos');
    })->name('videos');
    Route::get('/signin', function () {
        return view('signin');
    })->name('signin');
    Route::get('/signup', function () {
        return view('signup');
    })->name('signup');
    Route::get('/image', function () {
        return view('image');
    });
    Route::get('/line-chart', function () {
        return view('line-chart');
    })->name('line-chart');
    Route::get('/bar-chart', function () {
        return view('bar-chart');
    })->name('bar-chart');
    Route::get('/dash', function () {
        return view('dash');
    })->name('dash');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.delete-avatar');
    Route::put('/profile/address', [ProfileController::class, 'updateAddress'])->name('profile.address.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/data', [ProfileController::class, 'getUserData'])->name('profile.data');

    // System Settings
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/', [SystemController::class, 'index'])->name('index');
        Route::put('/update', [SystemController::class, 'update'])->name('update');
        Route::get('/clear-cache', [SystemController::class, 'clearCache'])->name('clear-cache');
        Route::get('/backup', [SystemController::class, 'backupDatabase'])->name('backup');
        Route::post('/toggle-maintenance', [SystemController::class, 'toggleMaintenance'])->name('toggle-maintenance');
        Route::post('/debug', [SystemController::class, 'debug'])->name('debug');
    });
    
});


Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('login.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);

require __DIR__.'/auth.php';
