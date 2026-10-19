# Project tree (generated for WORKPLAN Stage 1)

Excluded: vendor/, node_modules/, .git/, public/build/ (compiled), storage/ (logs ~250 MB, uploads, backups), bootstrap/cache, .env* secrets.
Markers: `[COPY]` duplicate/legacy copy file · `[SQL DUMP]` not read · `[BIG]` > 40 KB source (read by range only).

```
.DS_Store  6K
.editorconfig  258B
.env  (secrets, not read)
.env.example  (secrets, not read)
.gitattributes  186B
.gitignore  361B
.phpunit.result.cache  4K
IdeaShare.pkg  34299K
README.md  4K
REVIEW_AND_RECOMMENDATIONS.md  28K
SYSTEM_DOCUMENTATION.md  37K
WORKPLAN.md  8K
amazonblue_tree.txt  54K
artisan  425B
composer.json  3K
composer.lock  344K
deploy.sh  6K
fix_loans.php  0B
loans_main.sql  966K [SQL DUMP]
package-lock.json  142K
package.json  970B
phpunit.xml  1K
postcss.config.js  93B
tailwind.config.js  4K
vite.config.js  753B
app/
  Console/
    Kernel.php  956B
    Commands/
      BackfillBrokerFees.php  6K
      BackfillHouseAccount.php  2K
      BackfillHouseInterest.php  7K
      BalanceHouseAccounts.php  8K
      DetectNplLoans.php  3K
      FixLoanCycles.php  6K
      RepairLoanCycles.php  6K
      RiskAssess.php  3K
      UpdateLoanStatuses.php  8K
      WalletsBootstrap.php  5K
      WalletsCheck.php  4K
      WalletsReconcileFromJournal.php  3K
      WalletsSync.php  3K
  Helpers/
    CountryHelper.php  8K
    PhoneHelper.php  2K
    SystemHelper.php  15K
    helpers.php  1K
  Http/
    Kernel.php  3K
    Controllers/
      CaseController.php  54K [BIG]
      Controller.php  77B
      DashboardController.php  20K
      DisbursementController.php  9K
      HomeController.php  43K [BIG]
      InvestmentController.php  34K
      KycReviewController.php  5K
      LoanController.php  78K [BIG]
      PartnerController.php  14K
      ProfileController copy.php  19K [COPY]
      ProfileController.php  19K
      RepaymentController.php  8K
      ReportController.php  45K [BIG]
      UserController.php  25K
      Admin/
        SystemController.php  7K
      Auth/
        AuthenticatedSessionController.php  1K
        ConfirmablePasswordController.php  1K
        EmailVerificationNotificationController.php  630B
        EmailVerificationPromptController.php  564B
        GoogleController.php  165B
        NewPasswordController.php  2K
        PasswordController.php  795B
        PasswordResetLinkController.php  1K
        RegisteredUserController.php  3K
        VerifyEmailController.php  800B
    Middleware/
      CheckMaintenanceMode.php  3K
      CheckRole.php  783B
      EnsureStaff.php  694B
    Requests/
      ProfileUpdateRequest.php  1K
      Auth/
        LoginRequest.php  2K
  Models/
    ActionType.php  807B
    Address.php  2K
    AddressType.php  557B
    Admin.php  302B
    AgencyContact.php  704B
    Asset.php  3K
    AuditLog.php  1K
    BankAccount.php  370B
    Borrower.php  11K
    Broker.php  693B
    Category.php  341B
    Communication.php  3K
    Contact.php  2K
    ContactType.php  799B
    CourtHearing.php  1K
    DebtRecoveryCase.php  20K
    Disbursement.php  3K
    DocumentType.php  566B
    Employer.php  1K
    Employment.php  3K
    Enquiry.php  2K
    EventType.php  665B
    Investment.php  15K
    InvestmentFunding.php  2K
    InvestmentTransaction.php  645B
    LegalDeadline.php  2K
    LegalProceeding.php  3K
    Loan.php  35K
    LoanAgreementSection.php  5K
    LoanAgreementTemplate.php  2K
    LoanCycle.php  2K
    LoanLedger.php  2K
    LoanRiskAssessment.php  6K
    LoanType.php  454B
    Partner.php  8K
    PartnerTransaction.php  3K
    PaymentMethod.php  3K
    RecoveryAction.php  4K
    RecoveryAgency.php  1K
    RecoveryCaseNote.php  2K
    RecoveryDocument.php  2K
    RecoveryInstallment.php  3K
    RecoveryPaymentPlan.php  3K
    RecoveryPriority.php  815B
    RecoveryStatus.php  706B
    RecoveryTask.php  2K
    RecoveryTemplate.php  2K
    RecoveryTimeline.php  1K
    Repayment.php  2K
    RiskCategory.php  802B
    System.php  9K
    Teller.php  312B
    User.php  24K
    UserProfile.php  4K
  Pdf/
    LoanPDF.php  7K
  Providers/
    AppServiceProvider.php  3K
    SystemServiceProvider.php  349B
  Requests/
    ProfileUpdateRequest.php  743B
    Auth/
      LoginRequest.php  2K
  Services/
    BorrowingEligibility.php  5K
    CreditAssessment.php  20K
    CustomerWallets.php  15K
    DisbursementService.php  3K
    LoanAgreementService.php  38K
    LoanCalculator.php  56K [BIG]
    LoanProgress.php  4K
    NplDetectionService.php  8K
    PartnerLedger.php  4K
    PartnerPortfolio.php  3K
    RepaymentService.php  3K
    SignatureService.php  7K
  Support/
    Banks.php  1K
  View/
    Components/
      AppLayout.php  296B
      GuestLayout.php  300B
bootstrap/
  .DS_Store  6K
  app.php  1K
  providers.php  215B
config/
  app.php  4K
  auth.php  4K
  backup.php  12K
  cache.php  3K
  database.php  6K
  filesystems.php  3K
  lending.php  1K
  logging.php  4K
  mail.php  4K
  permission.php  8K
  queue.php  4K
  services.php  1K
  session.php  8K
  wallet.php  15K
database/
  .gitignore  10B
  database.sqlite  84K
  factories/
    UserFactory.php  1K
  migrations/
    0001_01_01_000010_create_users_table.php  3K
    0001_01_01_000011_create_password_reset_tokens_table.php  876B
    0001_01_01_000012_create_sessions_table.php  1005B
    0001_01_01_000013_create_cache_table.php  800B
    0001_01_01_000014_create_cache_locks_table.php  820B
    0001_01_01_000015_create_jobs_table.php  1015B
    0001_01_01_000016_create_job_batches_table.php  1K
    0001_01_01_000017_create_failed_jobs_table.php  998B
    0001_01_02_000000_create_wallets_table.php  1K
    0001_01_02_000001_create_transactions_table.php  2K
    0001_01_02_000002_create_transfers_table.php  2K
    0001_01_02_000003_create_permissions_table.php  913B
    0001_01_02_000004_create_roles_table.php  889B
    0001_01_02_000005_create_model_has_permissions_table.php  1K
    0001_01_02_000006_create_model_has_roles_table.php  1K
    0001_01_02_000007_create_role_has_permissions_table.php  1K
    2025_01_01_000001_create_action_types_table.php  970B
    2025_01_01_000002_create_address_types_table.php  1K
    2025_01_01_000003_create_addresses_table.php  2K
    2025_01_01_000004_create_admins_table.php  915B
    2025_01_01_000005_create_asset_types_table.php  1K
    2025_01_01_000006_create_audit_logs_table.php  1K
    2025_01_01_000007_create_bank_accounts_table.php  1K
    2025_01_01_000008_create_brokers_table.php  1K
    2025_01_01_000009_create_borrowers_table.php  2K
    2025_01_01_000010_create_bureau_names_table.php  954B
    2025_01_01_000011_create_categories_table.php  983B
    2025_01_01_000012_create_communication_statuses_table.php  986B
    2025_01_01_000013_create_communication_types_table.php  991B
    2025_01_01_000014_create_contact_types_table.php  1K
    2025_01_01_000015_create_contacts_table.php  2K
    2025_01_01_000016_create_document_statuses_table.php  971B
    2025_01_01_000017_create_document_types_table.php  991B
    2025_01_01_000018_create_employment_types_table.php  1K
    2025_01_01_000019_create_employments_table.php  2K
    2025_01_01_000020_create_enquiries_table.php  2K
    2025_01_01_000021_create_hardship_reasons_table.php  968B
    2025_01_01_000022_create_investments_table.php  4K
    2025_01_01_000023_create_legal_proceeding_types_table.php  1K
    2025_01_01_000024_create_loan_agreement_templates_table.php  1K
    2025_01_01_000025_create_loan_types_table.php  1K
    2025_01_01_000026_create_partners_table.php  3K
    2025_01_01_000027_create_loans_table.php  4K
    2025_01_01_000028_create_assets_table.php  2K
    2025_01_01_000029_create_loan_agreement_sections_table.php  2K
    2025_01_01_000030_create_loan_cycles_table.php  2K
    2025_01_01_000031_create_loan_ledger_table.php  2K
    2025_01_01_000032_create_loan_risk_assessments_table.php  2K
    2025_01_01_000033_create_partner_transactions_table.php  3K
    2025_01_01_000034_create_disbursements_table.php  2K
    2025_01_01_000035_create_investment_fundings_table.php  2K
    2025_01_01_000036_create_payment_method_types_table.php  1K
    2025_01_01_000037_create_payment_methods_table.php  2K
    2025_01_01_000038_create_recovery_agencies_table.php  1K
    2025_01_01_000039_create_agency_contacts_table.php  1K
    2025_01_01_000040_create_recovery_performance_metrics_table.php  2K
    2025_01_01_000041_create_recovery_priorities_table.php  972B
    2025_01_01_000042_create_recovery_statuses_table.php  985B
    2025_01_01_000043_create_debt_recovery_cases_table.php  3K
    2025_01_01_000044_create_agency_case_assignments_table.php  2K
    2025_01_01_000045_create_communications_table.php  2K
    2025_01_01_000046_create_credit_bureau_reports_table.php  3K
    2025_01_01_000047_create_financial_assessments_table.php  3K
    2025_01_01_000048_create_legal_deadlines_table.php  2K
    2025_01_01_000049_create_legal_proceedings_table.php  2K
    2025_01_01_000050_create_court_hearings_table.php  1K
    2025_01_01_000051_create_recovery_actions_table.php  3K
    2025_01_01_000052_create_recovery_case_notes_table.php  1K
    2025_01_01_000053_create_recovery_documents_table.php  2K
    2025_01_01_000054_create_recovery_payment_plans_table.php  2K
    2025_01_01_000055_create_recovery_installments_table.php  1K
    2025_01_01_000056_create_recovery_templates_table.php  1K
    2025_01_01_000057_create_repayments_table.php  3K
    2025_01_01_000058_create_residence_types_table.php  1K
    2025_01_01_000059_create_risk_categories_table.php  1K
    2025_01_01_000060_create_skip_tracing_table.php  2K
    2025_01_01_000061_create_system_table.php  2K
    2025_01_01_000062_create_tellers_table.php  957B
    2025_01_01_000063_create_user_profiles_table.php  2K
    2025_01_02_000000_create_borrower_contact_network_view.php  1K
    2025_01_02_000001_create_broker_performance_report_view.php  2K
    2025_01_02_000002_create_customer_health_scorecard_view.php  3K
    2025_01_02_000003_create_debt_recovery_summary_view.php  2K
    2025_01_02_000004_create_loan_type_analysis_view.php  1K
    2025_01_02_000005_create_portfolio_summary_view.php  1K
    2025_01_02_000006_create_recovery_officer_workload_view.php  1K
    2026_10_16_000000_add_kyc_review_fields_to_users_table.php  2K
    2026_10_17_000000_add_scoring_fields_to_loan_risk_assessments_table.php  2K
  seeders/
    DatabaseSeeder.php  833B
    DemoUsersSeeder.php  4K
    HouseAccountsSeeder.php  2K
    LookupTablesSeeder.php  24K
    RoleSeeder.php  12K
  sql/
    2026_10_15_live_upgrade.sql  9K
    2026_10_16_kyc_review.sql  4K
    2026_10_16_partner_journal_wallet_slug.sql  2K
    2026_10_17_risk_scoring.sql  5K
docs/
  audit/
    TREE.md  0B
lang/
public/
  .DS_Store  6K
  .htaccess  740B
  favicon.ico  4K
  hot  17B
  index.php  543B
  assets/
    css/
      styles.css  107K
public/assets/images/  (8 files, images/fonts, not listed)
    js/
      main.js  32K
  css/
    style.css  21K
  frontend/
    index.html  48K
    readme.txt  2K
    css/
      style.css  19K
    images/
      author-image.jpg  86K
      banner-image.jpg  101K
      commentor-item1.jpg  25K
      commentor-item2.jpg  25K
      commentor-item3.jpg  14K
      financial-solutions.jpg  101K
      hero-bg.jpg  101K
      logo1.png  2K
      logo2.png  4K
      logo3.png  2K
      logo4.png  2K
      logo6.png  2K
      portfolio-thumb-1.jpg  86K
      portfolio-thumb-2.jpg  21K
      portfolio-thumb-3.jpg  19K
      portfolio-thumb-4.jpg  128K
      portfolio-thumb-5.jpg  136K
      portfolio-thumb-6.jpg  44K
      portfolio-thumb-7.jpg  125K
      portfolio-thumb-8.jpg  19K
      post-thumb-1.jpg  38K
      post-thumb-2.jpg  61K
      post-thumb-3.jpg  36K
      post-thumb-4.jpg  219K
    js/
      jquery-1.11.0.min.js  94K [BIG]
      plugins.js  236K [BIG]
      script.js  6K
public/images/  (1 files, images/fonts, not listed)
  js/
    index.js  4K
    components/
      calendar-init.js  10K
      image-resize.js  7K
      map-01.js  1K
      charts/
        chart-01.js  2K
        chart-02.js  1K
        chart-03.js  2K
resources/
  .DS_Store  6K
  css/
    app.css  16K
  data/
    banks-kenya.txt  2K
  js/
    app.js  105B
    bootstrap.js  127B
    index.js  3K
    components/
      calendar-init.js  10K
      image-resize.js  7K
      map-01.js  1K
      charts/
        chart-01.js  2K
        chart-02.js  1K
        chart-03.js  2K
  views/
    .DS_Store  6K
    404.blade.php  2K
    alerts.blade.php  3K
    avatars.blade.php  3K
    badge.blade.php  4K
    bar-chart.blade.php  1K
    basic-tables.blade.php  2K
    blank.blade.php  1K
    buttons.blade.php  4K
    calendar.blade.php  741B
    dashboard.blade.php  6K
    form-elements.blade.php  83K [BIG]
    images.blade.php  2K
    index.blade.php  1K
    invoice.blade.php  18K
    line-chart.blade.php  1K
    maintenance.blade.php  3K
    signin.blade.php  20K
    signup.blade.php  26K
    videos.blade.php  3K
    welcome.blade copy 2.php  51K [COPY] [BIG]
    welcome.blade copy 3.php  48K [COPY] [BIG]
    welcome.blade copy 4.php  51K [COPY] [BIG]
    welcome.blade copy.php  31K [COPY]
    welcome.blade.php  32K
    admin/
      system/
        index.blade.php  124K [BIG]
        simple.blade.php  74K [BIG]
    auth/
      confirm-password.blade.php  886B
      forgot-password.blade.php  2K
      login.blade.php  3K
      register.blade.php  10K
      reset-password.blade.php  5K
      verify-email.blade.php  1K
    cases/
      create.blade.php  0B
      index.blade.php  2K
      show.blade.php  34K
    components/
      application-logo.blade.php  3K
      auth-session-status.blade.php  160B
      danger-button.blade.php  380B
      dropdown-link.blade.php  217B
      dropdown.blade.php  1K
      input-error.blade.php  241B
      input-label.blade.php  143B
      modal.blade.php  3K
      nav-link.blade.php  605B
      primary-button.blade.php  404B
      responsive-nav-link.blade.php  688B
      secondary-button.blade.php  393B
      text-input.blade.php  184B
    disbursements/
      create.blade.php  3K
      edit.blade.php  3K
      index.blade.php  12K
      show.blade.php  0B
    home/
      .DS_Store  6K
      asset-finance.blade.php  9K
      careers.blade.php  9K
      commodities.blade.php  9K
      company.blade.php  8K
      contact.blade.php  15K
      crb-solutions.blade.php  9K
      disclaimer.blade.php  6K
      emergency-solutions.blade.php  9K
      privacy.blade.php  6K
      services.blade.php  15K
      sitemap.blade.php  192B
      terms.blade.php  6K
      account/
        agreement.blade.php  1K
        borrow.blade.php  6K
        borrower.blade.php  12K
        loan.blade.php  5K
        loans.blade.php  3K
        partner.blade.php  4K
        profile.blade.php  6K
        statement.blade.php  2K
      kyc/
        contacts.blade.php  5K
        documents.blade.php  13K
        income.blade.php  10K
        personal.blade.php  5K
        review.blade.php  2K
        submitted.blade.php  1K
        partials/
          saved.blade.php  431B
      layouts/
        account.blade.php  4K
        app.blade.php  905B
        kyc.blade.php  4K
      partials/
        alert-dialog.blade.php  1K
        footer.blade.php  6K
        head.blade.php  2K
        header.blade.php  1K
        toast.blade.php  355B
        verified-tick.blade.php  523B
    investments/
      index.blade.php  2K
      show.blade.php  46K [BIG]
    kyc-reviews/
      index.blade.php  4K
      show.blade.php  11K
    layouts/
      app.blade.php  16K
      frontend.blade.php  2K
      guest.blade.php  1K
      navigation.blade.php  5K
    loans/
      .DS_Store  6K
      agreement.blade.php  0B
      assessment.blade.php  4K
      create.blade.php  58K [BIG]
      edit.blade.php  8K
      index.blade.php  2K
      report-pdf.blade.php  7K
      show.blade copy 2.php  83K [COPY] [BIG]
      show.blade copy.php  30K [COPY]
      show.blade new cycles.php  83K [COPY] [BIG]
      show.blade.php  40K
    partials/
      .DS_Store  10K
      breadcrumb.blade.php  998B
      calendar-event-modal.blade.php  13K
      common-grid-shape.blade.php  298B
      common-social-links.blade.php  5K
      datepicker.blade.php  2K
      header.blade.php  34K
      map-01.blade.php  5K
      media-card.blade.php  18K
      menu-icon.blade.php  27K
      overlay.blade.php  153B
      preloader.blade.php  371B
      sidebar.blade.php  35K
      top-card-group.blade.php  10K
      upcoming-schedule.blade.php  8K
      watchlist.blade.php  22K
      alert/
        alert-error.blade.php  4K
        alert-info.blade.php  4K
        alert-success.blade.php  3K
        alert-warning.blade.php  4K
      avatar/
        avatar-01.blade.php  1K
        avatar-02.blade.php  2K
        avatar-03.blade.php  2K
        avatar-04.blade.php  2K
      badge/
        badge-01.blade.php  2K
        badge-02.blade.php  1K
        badge-03.blade.php  7K
        badge-04.blade.php  6K
        badge-05.blade.php  7K
        badge-06.blade.php  6K
      buttons/
        button-01.blade.php  440B
        button-02.blade.php  3K
        button-03.blade.php  3K
        button-04.blade.php  662B
        button-05.blade.php  3K
        button-06.blade.php  3K
      card/
        investments-stats-card.blade.php  5K
        loans-sats-card.blade.php  2K
        partners-stats-card.blade.php  5K
        users-card.blade.php  3K
      chart/
        3layer-funnel.blade.php  3K
        active-vs-repaid.blade.php  901B
        amount-vs-balance.blade.php  0B
        average-days-late.blade.php  581B
        average-repayment-period.blade.php  607B
        cashflow-trend.blade.php  8K
        chart-01.blade.php  3K
        chart-02.blade.php  7K
        chart-03.blade.php  4K
        credit-score-distribution.blade.php  0B
        customer-balance.blade.php  8K
        days-late.blade.php  941B
        default-amounts.blade.php  0B
        default-breakdown.blade.php  0B
        delinquency-heatmap.blade.php  2K
        disbursement-amounts.blade.php  0B
        expected-repayments.blade.php  0B
        gender-distribution.blade.php  6K
        group-loan-status.blade.php  0B
        interest-penalties-brokerfees.blade.php  1K
        journal-entries.blade.php  0B
        loan-durations.blade.php  933B
        loan-funnel.blade.php  1K
        loan-status.blade.php  1K
        monthly-loans.blade copy.php  12K [COPY]
        monthly-loans.blade.php  33K
        net-earnings.blade.php  571B
        outstanding-at-due.blade.php  1017B
        outstanding-balance.blade.php  2K
        portfolio-performance.blade.php  4K
        principal-vs-interest.blade.php  0B
        repayments-trend.blade.php  1017B
        report-chart.blade.php  4K
        requested-vs-disbursed.blade.php  5K
        role-distribution.blade.php  2K
        savings-balance.blade.php  2K
        status-distribution.blade.php  7K
      grid/
        loan-sidebar-grid.blade.php  32K
      grid-image/
        image-01.blade.php  344B
        image-02.blade.php  413B
        image-03.blade.php  625B
      metric-group/
        metric-group-01.blade.php  26K
        metric-group-02.blade.php  9K
      modal/
        alert-modal.blade.php  6K
        cases-create-modal.blade.php  53K [BIG]
        delete.blade.php  7K
        disbursement-create-modal.blade.php  37K
        error.blade.php  3K
        flash-alert.blade.php  705B
        investments-create-modal.blade.php  115K [BIG]
        investments-show-modal.blade.php  17K
        loan-cycles-modal.blade.php  14K
        loan-payment-plan-modal.blade.php  32K
        loan-progress-drawer.blade.php  9K
        loan-rollover-modal.blade.php  27K
        loans-create-modal.blade.php  56K [BIG]
        partners-create-modal.blade.php  32K
        partners-show-modal.blade.php  21K
        repayment-create-modal.blade.php  24K
        reports-create-modal.blade.php  7K
      profile/
        profile-address-modal.blade.php  5K
        profile-info-modal.blade.php  10K
      recovery/
        action-timeline.blade.php  8K
        priority-badge.blade.php  2K
        status-badge.blade.php  2K
      table/
        table-06.blade.php  13K
        table-due-loans.blade.php  40K
        table-investments.blade.php  24K
        table-loans.blade.php  47K [BIG]
        table-partners.blade.php  26K
        table-recovery-cases.blade.php  20K
        table-reports-active-loans.blade.php  3K
        table-reports-cashflow.blade.php  2K
        table-reports-cic-reports.blade.php  6K
        table-reports-completed-loans.blade.php  3K
        table-reports-crb-report.blade.php  4K
        table-reports-customer-balance-summary.blade.php  4K
        table-reports-customer-reports.blade.php  4K
        table-reports-customer-savings.blade.php  3K
        table-reports-customer-statistics.blade.php  8K
        table-reports-defaulted-loans.blade.php  3K
        table-reports-expected-repayments.blade.php  3K
        table-reports-group-summary.blade.php  5K
        table-reports-journal-entries.blade.php  3K
        table-reports-loan-disbursement.blade.php  3K
        table-reports-loan-portfolio.blade.php  4K
        table-reports.blade.php  8K
        table-users.blade.php  85K [BIG]
    partners/
      index.blade.php  2K
      show.blade.php  30K
    profile/
      edit.blade.php  98K [BIG]
      show.blade.php  46K [BIG]
      partials/
        delete-user-form.blade.php  2K
        update-password-form.blade.php  2K
        update-profile-information-form.blade.php  3K
    repayments/
      create.blade.php  2K
      edit.blade.php  2K
      index.blade.php  4K
      show.blade.php  0B
    reports/
      index.blade.php  15K
      show.blade.php  17K
    users/
      create.blade.php  5K
      edit.blade.php  27K
      index.blade.php  262B
      show.blade.php  118K [BIG]
routes/
  auth.php  2K
  console.php  210B
  web.php  18K
tests/
  TestCase.php  926B
  Feature/
    ExampleTest.php  359B
    ProfileTest.php  2K
    Auth/
      AuthenticationTest.php  1K
      EmailVerificationTest.php  2K
      PasswordConfirmationTest.php  1K
      PasswordResetTest.php  2K
      PasswordUpdateTest.php  1K
      RegistrationTest.php  1K
  Unit/
    ExampleTest.php  243B
```

## Broken routes (route → controller method that does not exist): 17 of 176

- `GET|HEAD /admin/loans/{loan}/edit` → LoanController@adminEdit
- `GET|HEAD /auth/google` → Auth\GoogleController@redirect
- `GET|HEAD /auth/google/callback` → Auth\GoogleController@callback
- `GET|HEAD /cases/stats` → CaseController@getStats
- `GET|HEAD /cases/{case}/data` → CaseController@getCaseData
- `POST /investments/{investment}/funding` → InvestmentController@addFunding
- `POST /investments/{investment}/milestone` → InvestmentController@addMilestone
- `POST /investments/{investment}/note` → InvestmentController@addNote
- `GET|HEAD /loans/{loan}/chart-data` → LoanController@chartData
- `GET|HEAD /loans/{loan}/user-loans` → LoanController@getUserLoans
- `PUT|PATCH /profile/address` → ProfileController@updateAddress
- `DELETE /profile/avatar` → ProfileController@deleteAvatar
- `GET|HEAD /profile/data` → ProfileController@getUserData
- `POST /profile/password/confirm` → ProfileController@confirmPassword
- `GET|HEAD /system/backup` → Admin\SystemController@backupDatabase
- `POST /system/debug` → Admin\SystemController@debug
- `GET|HEAD /users/{user}/loans/create` → LoanController@createForUser

## Scheduler

- **No scheduled tasks are defined** (`php artisan schedule:list` is empty). Commands such as NPL detection only run when someone runs them by hand.

## Versions

- laravel/framework 12.69.3 · bavix/laravel-wallet 11.5.3 · spatie/laravel-permission 8.3.0 · PHP 8.3
