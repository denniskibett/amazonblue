<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Funding CRM and the business finance journey (owner, 20 Nov 2026).
 *
 * Funding CRM: businesses that need float, working capital, asset or trade finance (never individuals), from lawful
 * public sources only. Cold emails go from sales@sharet.africa; every reference starts with AIM- so replies never mix
 * with scharge (SC-) or Breve (BREVE-). Opt-outs and bounces go on a suppression list (Kenya Data Protection Act 2019).
 *
 * Finance applications: a business applies for asset or trade finance step by step; every step is saved and the
 * applicant resumes from a private link (only its hash is stored). Staff review them in a queue.
 *
 * SQL twin: database/sql/2026_11_20_funding_crm_and_finance_applications.sql (also the two permissions).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('funding_prospects')) {
            Schema::create('funding_prospects', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('reference', 20)->unique();                 // AIM-P-00001
                $t->string('name');
                $t->string('segment', 30)->index();                    // config/funding_crm.php segments
                $t->string('need', 20)->nullable();                    // float, working_capital, asset, trade
                $t->string('county', 60)->nullable();
                $t->string('town', 80)->nullable();
                $t->string('website')->nullable();
                $t->string('email')->nullable()->index();
                $t->string('phone', 40)->nullable();
                $t->string('contact_name')->nullable();
                $t->string('contact_role')->nullable();
                $t->string('source', 120);                             // where the details were published (required)
                $t->string('source_url')->nullable();
                $t->decimal('est_need', 15, 2)->nullable();
                $t->string('stage', 20)->default('new')->index();      // new, contacted, interested, applied, approved, won, lost
                $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $t->date('next_on')->nullable();
                $t->string('next_step')->nullable();
                $t->timestamp('last_emailed_at')->nullable();
                $t->timestamp('opted_out_at')->nullable();
                $t->unsignedBigInteger('application_id')->nullable()->index();
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('funding_campaigns')) {
            Schema::create('funding_campaigns', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('reference', 20)->unique();                 // AIM-C-001
                $t->string('name');
                $t->string('segment', 30)->nullable();                 // null = every segment
                $t->string('subject');
                $t->text('body');
                $t->string('status', 20)->default('draft');            // draft, sending, done
                $t->unsignedInteger('sent_count')->default(0);
                $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('funding_emails')) {
            Schema::create('funding_emails', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->foreignId('prospect_id')->constrained('funding_prospects')->cascadeOnDelete();
                $t->foreignId('campaign_id')->nullable()->constrained('funding_campaigns')->nullOnDelete();
                $t->string('reference', 24)->unique();                 // AIM-E-000001: in the subject so replies match
                $t->string('track', 32)->unique();                     // open, click and opt-out links
                $t->string('to');
                $t->string('subject');
                $t->text('body');
                $t->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
                $t->string('message_id')->nullable();
                $t->timestamp('sent_at')->nullable();
                $t->unsignedInteger('opens')->default(0);
                $t->timestamp('opened_at')->nullable();
                $t->timestamp('clicked_at')->nullable();
                $t->timestamp('replied_at')->nullable();
                $t->timestamp('bounced_at')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('funding_email_events')) {
            Schema::create('funding_email_events', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->foreignId('email_id')->constrained('funding_emails')->cascadeOnDelete();
                $t->unsignedBigInteger('prospect_id')->index();
                $t->string('kind', 10);                                // open, click
                $t->timestamp('occurred_at')->index();
                $t->string('via', 10)->default('direct');              // direct, gmail, apple, yahoo
                $t->string('ip_prefix', 45)->nullable();               // never the full address
                $t->char('country', 2)->nullable();
                $t->string('region', 80)->nullable();
                $t->string('city', 80)->nullable();
                $t->string('device', 20)->nullable();
                $t->string('os', 20)->nullable();
                $t->string('client', 30)->nullable();
                $t->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('funding_activities')) {
            Schema::create('funding_activities', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->foreignId('prospect_id')->constrained('funding_prospects')->cascadeOnDelete();
                $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('type', 20);                                // note, call, email, meeting, whatsapp, stage, follow_up
                $t->string('title');
                $t->text('body')->nullable();
                $t->timestamp('due_at')->nullable()->index();          // follow-ups only
                $t->timestamp('done_at')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('funding_suppressions')) {
            Schema::create('funding_suppressions', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('email')->unique();                         // lower case
                $t->string('reason', 20);                              // opt_out, bounce, complaint, manual
                $t->string('source')->nullable();
                $t->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('finance_applications')) {
            Schema::create('finance_applications', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('reference', 24)->unique();                 // AIM-AF-26-0001, AIM-TF-26-0001
                $t->string('product', 10)->index();                    // asset, trade
                $t->string('status', 20)->default('draft')->index();   // draft, submitted, in_review, more_info, approved, declined, withdrawn
                $t->json('steps_done')->nullable();                    // the steps saved so far
                $t->char('token_hash', 64)->unique();
                $t->timestamp('last_seen_at')->nullable();
                $t->string('contact_name');
                $t->string('email');
                $t->string('phone', 40);
                $t->json('business')->nullable();
                $t->json('details')->nullable();                       // the asset, or the trade deal
                $t->decimal('amount', 15, 2)->nullable();
                $t->unsignedSmallInteger('term_months')->nullable();
                $t->json('estimate')->nullable();
                $t->timestamp('consent_at')->nullable();
                $t->string('consent_ip', 45)->nullable();
                $t->timestamp('submitted_at')->nullable();
                $t->foreignId('prospect_id')->nullable()->constrained('funding_prospects')->nullOnDelete();
                $t->foreignId('loan_id')->nullable()->constrained('loans')->nullOnDelete();
                $t->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamp('reviewed_at')->nullable();
                $t->text('staff_note')->nullable();                    // what the applicant is told (more info, decline)
                $t->json('history')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('finance_application_documents')) {
            Schema::create('finance_application_documents', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->foreignId('finance_application_id')->constrained('finance_applications')->cascadeOnDelete();
                $t->string('type', 30);
                $t->string('path');
                $t->string('original_name')->nullable();
                $t->string('mime', 100)->nullable();
                $t->unsignedInteger('size')->default(0);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_application_documents');
        Schema::dropIfExists('finance_applications');
        Schema::dropIfExists('funding_suppressions');
        Schema::dropIfExists('funding_activities');
        Schema::dropIfExists('funding_email_events');
        Schema::dropIfExists('funding_emails');
        Schema::dropIfExists('funding_campaigns');
        Schema::dropIfExists('funding_prospects');
    }
};
