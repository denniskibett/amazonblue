<?php

namespace App\Providers;

use Bavix\Wallet\WalletConfigure;
use Illuminate\Support\ServiceProvider;
use App\Models\System;
use App\Helpers\SystemHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\CustomerWallets;
use App\Models\Repayment;
use App\Models\Disbursement;
use App\Models\Loan;
use App\Services\CreditAssessment;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The wallet tables are created by our own migrations (database/migrations, 0001_01_02_*),
        // so the package must not load its vendor copies as well.
        WalletConfigure::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel's own account emails in the branded design (App\Support\Emails)
        \Illuminate\Auth\Notifications\VerifyEmail::toMailUsing(fn ($user, string $url) => \App\Support\Emails::mail(
            \App\Support\Emails::verifyEmail(\App\Support\Emails::firstName($user->name), $url)));
        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(fn ($user, string $token) => \App\Support\Emails::mail(
            \App\Support\Emails::passwordReset(
                url(route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false)),
                (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60))));

        System::updated(function ($system) {
            SystemHelper::clearCache();
        });

        // Keep borrower cash wallets (bavix) in step with real cash: every disbursement or repayment
        // that is saved, edited, deleted or restored is synced once the database commit succeeds.
        // A failure is logged rather than blocking staff; `php artisan wallets:check` reports any gap.
        $sync = function (string $method) {
            return function ($model) use ($method) {
                DB::afterCommit(function () use ($model, $method) {
                    try {
                        app(CustomerWallets::class)->{$method}($model);
                    } catch (\Throwable $e) {
                        Log::error("Wallet sync failed for {$method} #{$model->id}", ['error' => $e->getMessage()]);
                    }
                });
            };
        };
        foreach (['saved', 'deleted'] as $event) {
            Disbursement::{$event}($sync('syncDisbursement'));
            Repayment::{$event}($sync('syncRepayment'));
        }
        Repayment::restored($sync('syncRepayment'));

        // Re-score the borrower's 4Cs whenever a loan, repayment or disbursement changes, so their
        // assessments follow the repayment journey. Batched: one re-score per borrower per request.
        $rescore = function ($model) {
            $userId = $model instanceof Loan ? $model->user_id : Loan::withTrashed()->whereKey($model->loan_id)->value('user_id');
            if ($userId) {
                CreditAssessment::rescoreLater((int) $userId);
            }
        };
        foreach (['saved', 'deleted'] as $event) {
            Loan::{$event}($rescore);
            Disbursement::{$event}($rescore);
            Repayment::{$event}($rescore);
        }
        Loan::restored($rescore);
        Repayment::restored($rescore);

        // Audit trail (audit_logs): who changed what, old/new values, IP. Never blocks the action.
        foreach ([Loan::class, Repayment::class, Disbursement::class, \App\Models\Partner::class,
                  \App\Models\PartnerTransaction::class, \App\Models\Investment::class, \App\Models\User::class,
                  \App\Models\DebtRecoveryCase::class, \App\Models\Business::class] as $audited) {
            $audited::observe(\App\Observers\AuditObserver::class);
        }
        // Spatie role changes on users (needs permission.events_enabled = true)
        \Illuminate\Support\Facades\Event::listen(\Spatie\Permission\Events\RoleAttachedEvent::class, function ($event) {
            \App\Services\AuditTrail::record($event->model->getTable(), $event->model->getKey(), 'update', null,
                ['roles_attached' => \App\Services\AuditTrail::roleNames($event->rolesOrIds)]);
        });
        \Illuminate\Support\Facades\Event::listen(\Spatie\Permission\Events\RoleDetachedEvent::class, function ($event) {
            \App\Services\AuditTrail::record($event->model->getTable(), $event->model->getKey(), 'update',
                ['roles_detached' => \App\Services\AuditTrail::roleNames($event->rolesOrIds)], null);
        });
    }
}
