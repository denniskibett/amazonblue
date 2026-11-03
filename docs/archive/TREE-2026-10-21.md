# Project tree (038211c 2026-10-21 23:58)

Committed files at that commit (`git ls-files`); live runs a fresh clone of the same commit, so live matches.
Excluded: vendor/, node_modules/, public/build/ (compiled), storage/ (logs, uploads, backups, reports), .env.
Markers: `[NEW]` added since 20 Oct 2026 (Stage 4) · `[BIG]` > 40 KB source.

1428 files.

```
./
  .editorconfig  258B
  .env.example  1K
  .gitattributes  186B
  .gitignore  373B
  IdeaShare.pkg  34298K
  README.md  3K
  REVIEW_AND_RECOMMENDATIONS.md  28K
  SYSTEM_DOCUMENTATION.md  37K
  WORKPLAN.md  7K
  amazonblue_tree.txt  53K
  artisan  425B
  composer.json  2K
  composer.lock  344K
  deploy.sh  6K
  fix_loans.php  0B
  loans_main.sql  1835K
  out.bin  2K
  package-lock.json  142K
  package.json  970B
  phpunit.xml  1K
  postcss.config.js  93B
  tailwind.config.js  5K
  vite.config.js  753B
app/Console/
  Kernel.php  956B
app/Console/Commands/
  AllocationsRestate.php  6K [NEW]
  ApprovalsDigest.php  1K [NEW]
  AuditPrune.php  3K
  BackfillBrokerFees.php  5K
  BackfillHouseAccount.php  3K
  BackfillHouseInterest.php  7K
  BalanceHouseAccounts.php  7K
  DemoSeed.php  12K
  DetectNplLoans.php  3K
  FilesMakePrivate.php  7K
  FixLoanCycles.php  7K
  HandlesBackfill.php  4K
  InvestmentsFixHouseFunding.php  3K
  LedgerAccrue.php  1K
  LedgerBackfill.php  6K
  LoansCheckIntegrity.php  7K
  LoansCreditCleanup.php  3K [NEW]
  LoansGraceFix.php  4K [NEW]
  LoansMergeChains.php  4K [NEW]
  LoansOwnerDecisions.php  2K [NEW]
  LoansRestate.php  10K
  LoansRolloverCleanup.php  6K [NEW]
  LoansWriteOffRepaid.php  4K [NEW]
  MakeSuperAdmin.php  2K
  MoneyBackfillColumns.php  3K
  MpesaRegisterUrls.php  945B [NEW]
  NotificationsReminders.php  2K [NEW]
  PartnersAccrueFixed.php  2K [NEW]
  PartnersLink.php  5K [NEW]
  RiskAssess.php  2K
  RolesSync.php  3K
  UpdateLoanStatuses.php  10K
  WalletsBootstrap.php  5K
  WalletsCheck.php  5K
  WalletsFixHouse.php  4K
  WalletsReconcileFromJournal.php  3K
  WalletsSync.php  2K
app/Helpers/
  CountryHelper.php  8K
  PhoneHelper.php  2K
  SystemHelper.php  15K
  helpers.php  1K
app/Http/Controllers/
  AuditLogController.php  1K
  BusinessReviewController.php  5K
  CaseController.php  60K [BIG]
  Controller.php  77B
  DashboardController.php  21K
  DisbursementController.php  10K
  HomeController.php  44K [BIG]
  InvestmentController.php  35K
  KycReviewController.php  4K
  LoanController.php  88K [BIG]
  MpesaCallbackController.php  3K [NEW]
  PartnerController.php  14K
  PrivateFileController.php  2K
  ProfileController.php  19K
  RepaymentController.php  11K
  ReportController.php  49K [BIG]
  UserController.php  27K
app/Http/Controllers/Admin/
  SystemController.php  12K
app/Http/Controllers/Auth/
  AuthenticatedSessionController.php  1K
  ConfirmablePasswordController.php  1K
  EmailVerificationNotificationController.php  648B
  EmailVerificationPromptController.php  582B
  NewPasswordController.php  2K
  PasswordController.php  795B
  PasswordResetLinkController.php  1K
  RegisteredUserController.php  2K
  VerifyEmailController.php  836B
app/Http/Controllers/Concerns/
  BorrowerJourneys.php  15K
  BorrowerRepayment.php  13K
  BusinessOnboarding.php  24K
  PartnerSelfService.php  13K
app/Http/Controllers/Staff/
  CashDeskController.php  10K
  CollectionsController.php  2K
  FinanceController.php  1K
  LoanDeskController.php  6K
  OperationsReviewController.php  20K [NEW]
  PaymentsController.php  4K [NEW]
  PortfolioController.php  1K [NEW]
  RequestsController.php  2K [NEW]
  SetupController.php  5K
  SignOffsController.php  1K [NEW]
app/Http/Middleware/
  CheckMaintenanceMode.php  1K
  CheckRole.php  783B
  EnsureStaff.php  694B
app/Http/Requests/
  ProfileUpdateRequest.php  1K
app/Http/Requests/Auth/
  LoginRequest.php  2K
app/Models/
  ActionType.php  807B
  Address.php  1K
  AddressType.php  557B
  Admin.php  302B
  AgencyCaseAssignment.php  591B
  AgencyContact.php  704B
  Asset.php  2K
  AuditLog.php  1K
  BankAccount.php  370B
  Borrower.php  10K
  Broker.php  693B
  Business.php  5K
  BusinessDocument.php  1K
  BusinessMember.php  1K
  Category.php  341B
  Communication.php  3K
  Contact.php  2K
  ContactType.php  799B
  CourtHearing.php  1K
  CreditBureauReport.php  587B
  DebtRecoveryCase.php  20K
  Disbursement.php  3K
  DocumentType.php  566B
  Employer.php  1K
  Employment.php  2K
  Enquiry.php  1K
  EventType.php  665B
  FinancialAssessment.php  589B
  Investment.php  19K
  InvestmentFunding.php  1K
  InvestmentTransaction.php  645B
  LegalDeadline.php  2K
  LegalProceeding.php  3K
  Loan.php  35K
  LoanAgreementSection.php  4K
  LoanAgreementTemplate.php  2K
  LoanCycle.php  3K
  LoanRiskAssessment.php  5K
  LoanType.php  454B
  MpesaTransaction.php  1022B [NEW]
  Partner.php  9K
  PartnerTransaction.php  2K
  PaymentClaim.php  692B [NEW]
  PaymentMethod.php  2K
  PaymentMethodType.php  644B
  RecoveryAction.php  3K
  RecoveryAgency.php  1K
  RecoveryCaseNote.php  1K
  RecoveryDocument.php  1K
  RecoveryInstallment.php  3K
  RecoveryPaymentPlan.php  3K
  RecoveryPriority.php  815B
  RecoveryStatus.php  706B
  RecoveryTask.php  2K
  RecoveryTemplate.php  2K
  RecoveryTimeline.php  1K
  Repayment.php  4K
  RiskCategory.php  802B
  SkipTracing.php  561B
  System.php  8K
  Teller.php  312B
  User.php  29K
  UserProfile.php  4K
app/Notifications/
  BusinessReviewed.php  981B
  BusinessSubmitted.php  696B
  CustomerEvent.php  1K [NEW]
  KycReviewed.php  768B
  KycSubmitted.php  648B
  LoanApplicationReceived.php  824B
  StaffAlert.php  716B
app/Notifications/Channels/
  SmsChannel.php  824B [NEW]
app/Observers/
  AuditObserver.php  3K
app/Pdf/
  LoanPDF.php  7K
app/Providers/
  AppServiceProvider.php  10K
  SystemServiceProvider.php  349B
app/Requests/
  ProfileUpdateRequest.php  743B
app/Requests/Auth/
  LoginRequest.php  2K
app/Services/
  ApprovalActions.php  2K [NEW]
  Approvals.php  4K [NEW]
  AuditTrail.php  2K
  BorrowingEligibility.php  5K
  CreditAssessment.php  20K
  CustomerEvents.php  4K [NEW]
  CustomerWallets.php  19K
  DisbursementService.php  2K
  Ledger.php  20K
  LedgerReports.php  5K [NEW]
  LoanAgreementService.php  41K [BIG]
  LoanCalculator.php  66K [BIG]
  LoanProgress.php  4K
  NplDetectionService.php  8K
  PartnerAccount.php  9K
  PartnerLedger.php  4K
  PartnerPortfolio.php  3K
  RepaymentService.php  2K
  Requests.php  7K [NEW]
  SignatureService.php  3K
app/Services/Loans/
  CreditCleanup.php  13K [NEW]
  CycleBalance.php  13K
  CycleOne.php  2K
  GraceFix.php  11K [NEW]
  LegacyChains.php  8K [NEW]
  LoanRepair.php  25K [NEW]
  LoanStatus.php  4K
  LoanSync.php  1K
  OwnerDecisions.php  6K [NEW]
  Period.php  711B
  RepaymentSplit.php  9K [NEW]
  Reversal.php  1K [NEW]
  Rollover.php  9K
  RolloverCleanup.php  21K [NEW]
  WriteOff.php  3K [NEW]
app/Services/Mpesa/
  Daraja.php  4K [NEW]
  PaymentRecorder.php  10K [NEW]
  StatementReconciler.php  3K [NEW]
app/Services/Sms/
  SmsSender.php  1K [NEW]
app/Support/
  Banks.php  1K
  BorrowerLoanState.php  11K
  CustomerNotifier.php  7K
  Emails.php  23K
  Handle.php  4K
  PartnerFunding.php  3K
  PrivateFiles.php  3K
app/View/Components/
  AppLayout.php  296B
  GuestLayout.php  300B
bootstrap/
  app.php  3K
  providers.php  215B
bootstrap/cache/
  .gitignore  14B
config/
  app.php  4K
  auth.php  3K
  az.php  1K
  backup.php  12K
  cache.php  3K
  database.php  5K
  filesystems.php  2K
  lending.php  13K
  logging.php  4K
  mail.php  3K
  permission.php  7K
  queue.php  3K
  services.php  1K
  session.php  7K
  wallet.php  15K
database/
  .gitignore  10B
database/factories/
  UserFactory.php  1K
database/migrations/
  0001_01_01_000010_create_users_table.php  2K
  0001_01_01_000011_create_password_reset_tokens_table.php  876B
  0001_01_01_000012_create_sessions_table.php  1005B
  0001_01_01_000013_create_cache_table.php  800B
  0001_01_01_000014_create_cache_locks_table.php  820B
  0001_01_01_000015_create_jobs_table.php  1015B
  0001_01_01_000016_create_job_batches_table.php  1K
  0001_01_01_000017_create_failed_jobs_table.php  998B
  0001_01_02_000000_create_wallets_table.php  1K
  0001_01_02_000001_create_transactions_table.php  1K
  0001_01_02_000002_create_transfers_table.php  1K
  0001_01_02_000003_create_permissions_table.php  913B
  0001_01_02_000004_create_roles_table.php  889B
  0001_01_02_000005_create_model_has_permissions_table.php  1K
  0001_01_02_000006_create_model_has_roles_table.php  1K
  0001_01_02_000007_create_role_has_permissions_table.php  1K
  2025_01_01_000001_create_action_types_table.php  970B
  2025_01_01_000002_create_address_types_table.php  1K
  2025_01_01_000003_create_addresses_table.php  1K
  2025_01_01_000004_create_admins_table.php  915B
  2025_01_01_000005_create_asset_types_table.php  1K
  2025_01_01_000006_create_audit_logs_table.php  1K
  2025_01_01_000007_create_bank_accounts_table.php  1K
  2025_01_01_000008_create_brokers_table.php  1K
  2025_01_01_000009_create_borrowers_table.php  1K
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
  2025_01_01_000026_create_partners_table.php  2K
  2025_01_01_000027_create_loans_table.php  4K
  2025_01_01_000028_create_assets_table.php  2K
  2025_01_01_000029_create_loan_agreement_sections_table.php  1K
  2025_01_01_000030_create_loan_cycles_table.php  1K
  2025_01_01_000031_create_loan_ledger_table.php  1K
  2025_01_01_000032_create_loan_risk_assessments_table.php  1K
  2025_01_01_000033_create_partner_transactions_table.php  2K
  2025_01_01_000034_create_disbursements_table.php  2K
  2025_01_01_000035_create_investment_fundings_table.php  2K
  2025_01_01_000036_create_payment_method_types_table.php  1K
  2025_01_01_000037_create_payment_methods_table.php  2K
  2025_01_01_000038_create_recovery_agencies_table.php  1K
  2025_01_01_000039_create_agency_contacts_table.php  1K
  2025_01_01_000040_create_recovery_performance_metrics_table.php  1K
  2025_01_01_000041_create_recovery_priorities_table.php  972B
  2025_01_01_000042_create_recovery_statuses_table.php  985B
  2025_01_01_000043_create_debt_recovery_cases_table.php  3K
  2025_01_01_000044_create_agency_case_assignments_table.php  1K
  2025_01_01_000045_create_communications_table.php  2K
  2025_01_01_000046_create_credit_bureau_reports_table.php  2K
  2025_01_01_000047_create_financial_assessments_table.php  2K
  2025_01_01_000048_create_legal_deadlines_table.php  1K
  2025_01_01_000049_create_legal_proceedings_table.php  2K
  2025_01_01_000050_create_court_hearings_table.php  1K
  2025_01_01_000051_create_recovery_actions_table.php  2K
  2025_01_01_000052_create_recovery_case_notes_table.php  1K
  2025_01_01_000053_create_recovery_documents_table.php  1K
  2025_01_01_000054_create_recovery_payment_plans_table.php  2K
  2025_01_01_000055_create_recovery_installments_table.php  1K
  2025_01_01_000056_create_recovery_templates_table.php  1K
  2025_01_01_000057_create_repayments_table.php  2K
  2025_01_01_000058_create_residence_types_table.php  1K
  2025_01_01_000059_create_risk_categories_table.php  1K
  2025_01_01_000060_create_skip_tracing_table.php  2K
  2025_01_01_000061_create_system_table.php  2K
  2025_01_01_000062_create_tellers_table.php  957B
  2025_01_01_000063_create_user_profiles_table.php  2K
  2025_01_02_000000_create_borrower_contact_network_view.php  1K
  2025_01_02_000001_create_broker_performance_report_view.php  1K
  2025_01_02_000002_create_customer_health_scorecard_view.php  2K
  2025_01_02_000003_create_debt_recovery_summary_view.php  1K
  2025_01_02_000004_create_loan_type_analysis_view.php  1K
  2025_01_02_000005_create_portfolio_summary_view.php  1K
  2025_01_02_000006_create_recovery_officer_workload_view.php  1K
  2026_10_16_000000_add_kyc_review_fields_to_users_table.php  1K
  2026_10_17_000000_add_scoring_fields_to_loan_risk_assessments_table.php  2K
  2026_10_18_000001_add_agreement_fields_to_loans_table.php  2K
  2026_10_18_000021_change_role_to_string_on_users_table.php  1K
  2026_10_18_000041_add_handle_to_users_table.php  1018B
  2026_10_18_000042_add_handle_to_partners_table.php  1K
  2026_10_18_000051_add_written_off_status_to_loans_table.php  1K
  2026_10_18_000052_add_repayment_id_to_recovery_actions_table.php  1K
  2026_10_19_000011_create_businesses_table.php  3K
  2026_10_19_000012_create_business_members_table.php  1K
  2026_10_19_000013_create_business_documents_table.php  1K
  2026_10_20_000001_add_selfie_paths_to_users_table.php  1K
  2026_10_20_000101_drop_loan_ledger_table.php  974B
  2026_10_20_000102_add_ledger_columns_to_repayments_table.php  2K
  2026_10_20_000103_add_ledger_columns_to_disbursements_table.php  1K
  2026_10_20_000201_add_engine_columns_to_loan_cycles_table.php  2K
  2026_10_21_000101_add_merged_into_loan_id_to_loans_table.php  1K [NEW]
  2026_10_21_000201_add_return_type_to_investments_table.php  1K [NEW]
  2026_10_21_000202_add_broker_penalty_rate_to_loans_table.php  1K [NEW]
  2026_10_21_000301_create_mpesa_transactions_table.php  2K [NEW]
  2026_10_21_000302_create_payment_claims_table.php  2K [NEW]
  2026_10_21_000401_create_approvals_table.php  2K [NEW]
  2026_10_21_000402_create_approval_limits_table.php  1K [NEW]
  2026_10_21_000403_create_borrower_requests_table.php  1K [NEW]
  2026_10_21_000404_create_partner_requests_table.php  1K [NEW]
  2026_10_21_000405_create_teller_day_closes_table.php  1K [NEW]
  2026_10_21_000406_create_loan_type_versions_table.php  1K [NEW]
  2026_10_21_000501_create_notifications_table.php  984B [NEW]
  2026_10_21_000601_drop_borrower_contact_network_view.php  679B [NEW]
  2026_10_21_000602_drop_broker_performance_report_view.php  682B [NEW]
  2026_10_21_000603_drop_customer_health_scorecard_view.php  682B [NEW]
  2026_10_21_000604_drop_debt_recovery_summary_view.php  670B [NEW]
  2026_10_21_000605_drop_loan_type_analysis_view.php  661B [NEW]
  2026_10_21_000606_drop_portfolio_summary_view.php  658B [NEW]
  2026_10_21_000607_drop_recovery_officer_workload_view.php  682B [NEW]
database/seeders/
  DatabaseSeeder.php  833B
  DemoUsersSeeder.php  3K
  HouseAccountsSeeder.php  1K
  LookupTablesSeeder.php  24K
  RoleSeeder.php  11K
database/sql/
  2026_10_15_live_upgrade.sql  8K
  2026_10_16_kyc_review.sql  3K
  2026_10_16_partner_journal_wallet_slug.sql  1K
  2026_10_17_risk_scoring.sql  4K
  2026_10_18_loan_agreement_fields.sql  4K
  2026_10_18_loan_written_off_status.sql  1K
  2026_10_18_recovery_action_repayment.sql  1K
  2026_10_18_users_role_string.sql  1K
  2026_10_18_wallet_handles.sql  2K
  2026_10_19_businesses.sql  12K
  2026_10_20_broker_kyc_access.sql  2K
  2026_10_20_drop_loan_ledger.sql  1K
  2026_10_20_kyc_selfies.sql  2K
  2026_10_20_loan_cycle_engine_columns.sql  6K
  2026_10_20_money_row_columns.sql  4K
  2026_10_20_system_settings_gate.sql  2K
  2026_10_20_users_id_type_values.sql  1K
  2026_10_21_drop_unused_views.sql  3K [NEW]
  2026_10_21_fix_disbursement_301_date.sql  1K [NEW]
  2026_10_21_fix_disbursement_629_date.sql  1K [NEW]
  2026_10_21_loans_merged_into.sql  1K [NEW]
  2026_10_21_mpesa_tables.sql  4K [NEW]
  2026_10_21_notifications.sql  1K [NEW]
  2026_10_21_phase3_columns.sql  2K [NEW]
  2026_10_21_phase5_tables.sql  8K [NEW]
docs/
  DEPLOY.md  5K
docs/audit/
  AUDIT.md  14K
  DEPLOY-2026-10-18.md  4K
  TREE.md  23K
  design-screens.md  4K
docs/audit/modules/
  A-customer.md  48K
  B-loan-engine.md  35K
  C-money.md  35K
  D-staff-roles-journeys.md  44K
  E-business.md  9K
docs/stage2/
  DESIGN-TOKENS.md  2K
  README.md  3K
  SUMMARY.md  8K
docs/stage2/screens/active/
  components-desktop.png  131K
  components-phone.png  81K
  components.html  15K
  home-desktop.png  284K
  home-phone.png  169K
  home.html  10K
docs/stage2/screens/admin/
  components-desktop.png  127K
  components-phone.png  65K
  components.html  98K
docs/stage2/screens/borrower/active/
  extension-desktop.png  143K
  extension-phone.png  90K
  extension.html  9K
  help-desktop.png  114K
  help-phone.png  70K
  help.html  10K
  home-desktop.png  277K
  home-phone.png  168K
  home.html  11K
  loan-desktop.png  229K
  loan-phone.png  147K
  loan.html  10K
  notifications-desktop.png  156K
  notifications-phone.png  86K
  notifications.html  10K
  statement-desktop.png  122K
  statement-phone.png  70K
  statement.html  9K
docs/stage2/screens/borrower/approved/
  home-desktop.png  249K
  home-phone.png  144K
  home.html  9K
  loan-desktop.png  131K
  loan-phone.png  83K
  loan.html  9K
docs/stage2/screens/borrower/inrecovery/
  home-desktop.png  265K
  home-phone.png  168K
  home.html  14K
  loan-desktop.png  156K
  loan-phone.png  80K
  loan.html  11K
  statement-desktop.png  138K
  statement-phone.png  71K
  statement.html  11K
docs/stage2/screens/borrower/overdue/
  extension-desktop.png  163K
  extension-phone.png  92K
  extension.html  9K
  home-desktop.png  278K
  home-phone.png  172K
  home.html  12K
  loan-desktop.png  229K
  loan-phone.png  103K
  loan.html  10K
docs/stage2/screens/borrower/pending/
  home-desktop.png  252K
  home-phone.png  156K
  home.html  10K
  loan-desktop.png  137K
  loan-phone.png  85K
  loan.html  9K
docs/stage2/screens/borrower/plan/
  home-desktop.png  268K
  home-phone.png  167K
  home.html  11K
  loan-desktop.png  216K
  loan-phone.png  123K
  loan.html  10K
  plan-desktop.png  101K
  plan-phone.png  84K
  plan.html  7K
docs/stage2/screens/borrower/rejected/
  borrow-desktop.png  116K
  borrow-phone.png  76K
  borrow.html  15K
  home-desktop.png  253K
  home-phone.png  153K
  home.html  10K
  loan-desktop.png  139K
  loan-phone.png  82K
  loan.html  9K
docs/stage2/screens/borrower/writtenoff/
  home-desktop.png  246K
  home-phone.png  147K
  home.html  12K
  loan-desktop.png  211K
  loan-phone.png  136K
  loan.html  11K
  statement-desktop.png  133K
  statement-phone.png  72K
  statement.html  10K
docs/stage2/screens/business/admin/
  01-reviews-list-desktop.png  96K
  01-reviews-list-phone.png  47K
  01-reviews-list.html  93K
  02-review-detail-approve-error-desktop.png  250K
  02-review-detail-approve-error-phone.png  197K
  02-review-detail-approve-error.html  105K
  03-review-detail-demo-pdfs-desktop.png  253K
  03-review-detail-demo-pdfs-phone.png  57K
  03-review-detail-demo-pdfs.html  106K
  04-reviews-rejected-tab-desktop.png  84K
  04-reviews-rejected-tab-phone.png  45K
  04-reviews-rejected-tab.html  93K
  05-review-detail-resubmitted-desktop.png  244K
  05-review-detail-resubmitted-phone.png  53K
  05-review-detail-resubmitted.html  104K
  06-approved-confirmation-desktop.png  185K
  06-approved-confirmation-phone.png  54K
  06-approved-confirmation.html  103K
  07-review-approved-outcome-desktop.png  246K
  07-review-approved-outcome-phone.png  54K
  07-review-approved-outcome.html  103K
docs/stage2/screens/business/borrower/
  01-account-home-desktop.png  298K
  01-account-home-phone.png  222K
  01-account-home.html  10K
  02-start-desktop.png  289K
  02-start-phone.png  92K
  02-start.html  5K
  03-details-desktop.png  374K
  03-details-phone.png  133K
  03-details.html  11K
  04-members-start-desktop.png  344K
  04-members-start-phone.png  124K
  04-members-start.html  9K
  05-members-over-100-desktop.png  376K
  05-members-over-100-phone.png  146K
  05-members-over-100.html  11K
  05b-members-done-desktop.png  285K
  05b-members-done-phone.png  86K
  05b-members-done.html  7K
  06-documents-missing-desktop.png  344K
  06-documents-missing-phone.png  117K
  06-documents-missing.html  15K
  07-documents-done-desktop.png  311K
  07-documents-done-phone.png  99K
  07-documents-done.html  9K
  08-financials-desktop.png  256K
  08-financials-phone.png  72K
  08-financials.html  10K
  09-review-desktop.png  303K
  09-review-phone.png  105K
  09-review.html  7K
  10-submitted-desktop.png  241K
  10-submitted-phone.png  67K
  10-submitted.html  4K
  11-business-under-review-desktop.png  146K
  11-business-under-review-phone.png  167K
  11-business-under-review.html  8K
  12-account-home-rejected-desktop.png  300K
  12-account-home-rejected-phone.png  224K
  12-account-home-rejected.html  10K
  13-business-rejected-desktop.png  146K
  13-business-rejected-phone.png  164K
  13-business-rejected.html  8K
  14-review-after-reject-desktop.png  336K
  14-review-after-reject-phone.png  129K
  14-review-after-reject.html  8K
  15-business-approved-desktop.png  142K
  15-business-approved-phone.png  165K
  15-business-approved.html  8K
  16-details-locked-desktop.png  310K
  16-details-locked-phone.png  129K
  16-details-locked.html  12K
docs/stage2/screens/business/new/
  01-start-kyc-not-approved-desktop.png  290K
  01-start-kyc-not-approved-phone.png  94K
  01-start-kyc-not-approved.html  5K
docs/stage2/screens/partner/
  dual-borrower-home-desktop.png  246K
  dual-borrower-home-phone.png  188K
  dual-borrower-home.html  9K
  dual-partner-home-desktop.png  262K
  dual-partner-home-phone.png  206K
  dual-partner-home.html  10K
  earnings-desktop.png  178K
  earnings-empty-desktop.png  54K
  earnings-empty-phone.png  48K
  earnings-empty.html  6K
  earnings-phone.png  140K
  earnings.html  8K
  edge-no-partner-home-desktop.png  63K
  edge-no-partner-home-phone.png  66K
  edge-no-partner-home.html  6K
  edge-no-partner-statement-desktop.png  60K
  edge-no-partner-statement-phone.png  64K
  edge-no-partner-statement.html  6K
  edge-zero-balance-home-desktop.png  259K
  edge-zero-balance-home-phone.png  205K
  edge-zero-balance-home.html  10K
  edge-zero-balance-withdraw-desktop.png  109K
  edge-zero-balance-withdraw-phone.png  112K
  edge-zero-balance-withdraw.html  7K
  funds-desktop.png  140K
  funds-phone.png  142K
  funds.html  10K
  home-desktop.png  288K
  home-phone.png  240K
  home.html  12K
  investment-desktop.png  150K
  investment-phone.png  154K
  investment.html  10K
  investments-desktop.png  93K
  investments-empty-desktop.png  83K
  investments-empty-phone.png  77K
  investments-empty.html  7K
  investments-phone.png  97K
  investments.html  7K
  loan-desktop.png  185K
  loan-phone.png  168K
  loan.html  8K
  loans-desktop.png  73K
  loans-empty-desktop.png  57K
  loans-empty-phone.png  50K
  loans-empty.html  6K
  loans-phone.png  74K
  loans.html  8K
  statement-desktop.png  109K
  statement-filtered-desktop.png  84K
  statement-filtered-phone.png  81K
  statement-filtered.html  9K
  statement-phone.png  96K
  statement.html  10K
  withdraw-desktop.png  124K
  withdraw-over-balance-desktop.png  128K
  withdraw-over-balance-phone.png  131K
  withdraw-over-balance.html  10K
  withdraw-phone.png  127K
  withdraw.html  10K
docs/stage2/screens/repay/active/
  home-desktop.png  289K
  home-phone.png  211K
  home.html  10K
  loan-desktop.png  242K
  loan-phone.png  234K
  loan.html  10K
  receipt-desktop.png  93K
  receipt-phone.png  100K
  receipt.html  7K
  repay-desktop.png  313K
  repay-phone.png  281K
  repay.html  12K
  stk-failed-desktop.png  311K
  stk-failed-phone.png  285K
  stk-failed.html  16K
  stk-received-desktop.png  307K
  stk-received-phone.png  282K
  stk-received.html  16K
  stk-sending-desktop.png  301K
  stk-sending-phone.png  277K
  stk-sending.html  16K
  stk-timeout-desktop.png  306K
  stk-timeout-phone.png  281K
  stk-timeout.html  16K
  stk-waiting-desktop.png  311K
  stk-waiting-phone.png  285K
  stk-waiting.html  16K
docs/stage2/screens/repay/overdue/
  checking-desktop.png  365K
  checking-phone.png  334K
  checking.html  13K
  home-desktop.png  349K
  home-phone.png  312K
  home.html  13K
  loan-desktop.png  302K
  loan-phone.png  290K
  loan.html  11K
  repay-desktop.png  328K
  repay-phone.png  294K
  repay.html  12K
docs/stage2/screens/repay/rejected/
  home-desktop.png  286K
  home-phone.png  212K
  home.html  9K
  repay-none-desktop.png  53K
  repay-none-phone.png  61K
  repay-none.html  6K
  repay-rejected-desktop.png  51K
  repay-rejected-phone.png  59K
  repay-rejected.html  6K
docs/stage2/screens/repay/repay-pending/
  repay-desktop.png  53K
  repay-phone.png  62K
  repay.html  6K
docs/stage2/screens/repay/repay-recovery/
  home-desktop.png  349K
  home-phone.png  279K
  home.html  11K
  repay-desktop.png  295K
  repay-phone.png  264K
  repay.html  11K
docs/stage2/screens/repay/repay-repaid/
  book-entry-desktop.png  102K
  book-entry-phone.png  110K
  book-entry.html  8K
  closed-desktop.png  98K
  closed-phone.png  105K
  closed.html  7K
  loan-desktop.png  246K
  loan-phone.png  233K
  loan.html  10K
docs/stage2/screens/staff/admin/
  approvals-desktop.png  194K
  approvals-phone.png  63K
  approvals.html  180K
  approved-desktop.png  99K
  approved-phone.png  57K
  approved.html  91K
  collections-desktop.png  184K
  collections-phone.png  62K
  collections.html  127K
  disburse-queue-empty-desktop.png  84K
  disburse-queue-empty-phone.png  41K
  disburse-queue-empty.html  86K
  end-of-day-desktop.png  140K
  end-of-day-phone.png  59K
  end-of-day.html  92K
  inbox-desktop.png  109K
  inbox-phone.png  57K
  inbox.html  88K
  partner-payouts-desktop.png  139K
  partner-payouts-phone.png  66K
  partner-payouts.html  93K
  products-desktop.png  140K
  products-phone.png  45K
  products.html  92K
  receive-loan-desktop.png  155K
  receive-loan-phone.png  54K
  receive-loan.html  99K
  roles-desktop.png  122K
  roles-manage-desktop.png  123K
  roles-manage-phone.png  57K
  roles-manage.html  322K
  roles-phone.png  58K
  roles.html  316K
  wallets-desktop.png  157K
  wallets-phone.png  63K
  wallets.html  94K
docs/stage2/screens/staff/auditor/
  approvals-readonly-desktop.png  168K
  approvals-readonly-phone.png  63K
  approvals-readonly.html  106K
  disburse-queue-desktop.png  89K
  disburse-queue-phone.png  49K
  disburse-queue.html  85K
  end-of-day-empty-desktop.png  101K
  end-of-day-empty-phone.png  51K
  end-of-day-empty.html  87K
  inbox-desktop.png  66K
  inbox-phone.png  27K
  inbox.html  81K
  receive-403.html  2K
  roles-readonly-desktop.png  122K
  roles-readonly-phone.png  58K
  roles-readonly.html  314K
docs/stage2/screens/staff/manager/
  approvals-desktop.png  194K
  approvals-phone.png  63K
  approvals.html  179K
  inbox-desktop.png  109K
  inbox-phone.png  57K
  inbox.html  87K
  receive-none-desktop.png  74K
  receive-none-phone.png  36K
  receive-none.html  84K
  wallets-desktop.png  157K
  wallets-phone.png  63K
  wallets.html  93K
docs/stage2/screens/staff/recovery/
  collections-desktop.png  178K
  collections-mine-empty-desktop.png  116K
  collections-mine-empty-phone.png  65K
  collections-mine-empty.html  75K
  collections-phone.png  62K
  collections.html  112K
  inbox-desktop.png  59K
  inbox-phone.png  27K
  inbox.html  68K
docs/stage2/screens/staff/teller/
  approvals-readonly-desktop.png  157K
  approvals-readonly-phone.png  63K
  approvals-readonly.html  89K
  disburse-desktop.png  114K
  disburse-phone.png  51K
  disburse-queue-desktop.png  72K
  disburse-queue-phone.png  41K
  disburse-queue.html  68K
  disburse.html  74K
  end-of-day-desktop.png  125K
  end-of-day-phone.png  59K
  end-of-day.html  72K
  inbox-desktop.png  54K
  inbox-phone.png  27K
  inbox.html  65K
  receive-loan-desktop.png  142K
  receive-loan-phone.png  51K
  receive-loan.html  75K
  receive-search-desktop.png  67K
  receive-search-phone.png  35K
  receive-search.html  68K
  roles-403.html  2K
docs/stage2/walkthroughs/
  borrower.md  8K
  business.md  8K
  partner.md  7K
  repay.md  5K
  staff.md  8K
docs/stage3/
  REVIEW.md  24K
  STAGE4-PLAN.md  35K
docs/stage4/
  DEPLOY-F1-GRACE.md  3K [NEW]
  DEPLOY-F2A-ROLLOVERS.md  2K [NEW]
  DEPLOY-F2C-CREDIT-DISCOUNT.md  2K [NEW]
  DEPLOY-OWNER-DECISIONS.md  1K [NEW]
  DEPLOY-PHASE1.md  10K
  DEPLOY-PHASE2.md  8K
  DEPLOY-PHASE3.md  5K [NEW]
  DEPLOY-PHASES-4-7.md  5K [NEW]
  PHASE0.md  2K
  RETENTION.md  2K
lang/vendor/backup/ar/
  notifications.php  3K
lang/vendor/backup/bg/
  notifications.php  4K
lang/vendor/backup/bn/
  notifications.php  4K
lang/vendor/backup/cs/
  notifications.php  2K
lang/vendor/backup/da/
  notifications.php  2K
lang/vendor/backup/de/
  notifications.php  2K
lang/vendor/backup/en/
  notifications.php  2K
lang/vendor/backup/es/
  notifications.php  3K
lang/vendor/backup/fa/
  notifications.php  3K
lang/vendor/backup/fi/
  notifications.php  2K
lang/vendor/backup/fr/
  notifications.php  3K
lang/vendor/backup/he/
  notifications.php  3K
lang/vendor/backup/hi/
  notifications.php  4K
lang/vendor/backup/hr/
  notifications.php  2K
lang/vendor/backup/id/
  notifications.php  2K
lang/vendor/backup/it/
  notifications.php  2K
lang/vendor/backup/ja/
  notifications.php  3K
lang/vendor/backup/kk/
  notifications.php  4K
lang/vendor/backup/ko/
  notifications.php  2K
lang/vendor/backup/nl/
  notifications.php  2K
lang/vendor/backup/no/
  notifications.php  2K
lang/vendor/backup/pl/
  notifications.php  3K
lang/vendor/backup/pt/
  notifications.php  2K
lang/vendor/backup/pt_BR/
  notifications.php  2K
lang/vendor/backup/ro/
  notifications.php  3K
lang/vendor/backup/ru/
  notifications.php  3K
lang/vendor/backup/sk/
  notifications.php  2K
lang/vendor/backup/tr/
  notifications.php  2K
lang/vendor/backup/uk/
  notifications.php  3K
lang/vendor/backup/zh_CN/
  notifications.php  2K
lang/vendor/backup/zh_TW/
  notifications.php  2K
public/
  .htaccess  740B
  favicon.ico  3K
  index.php  543B
public/assets/css/
  business.css  4K
  styles.css  129K
public/assets/images/
  careers-office.jpg  162K
  invest-mombasa-port.jpg  290K
  mombasa-highway-trucks.jpg  168K
  nairobi-cbd.jpg  233K
  nairobi-entrepreneur.jpg  266K
  og-aimzone.jpg  84K
  port-of-mombasa.jpg  309K
  team-nairobi.jpg  145K
public/assets/images/logo/
  aimzone-horizontal-blue.svg  16K
  aimzone-horizontal-white.svg  16K
  aimzone-icon-blue.svg  9K
  aimzone-icon-white.svg  9K
  aimzone-primary-blue.svg  17K
  aimzone-primary-white.svg  17K
  apple-touch-icon.png  10K
  favicon.ico  3K
  favicon.svg  9K
  icon-192.png  11K
  icon-512.png  22K
  site.webmanifest  256B
public/assets/js/
  main.js  32K
public/css/
  style.css  21K
public/frontend/
  index.html  47K
  readme.txt  2K
public/frontend/css/
  style.css  18K
public/frontend/images/
  author-image.jpg  86K
  banner-image.jpg  101K
  commentor-item1.jpg  24K
  commentor-item2.jpg  24K
  commentor-item3.jpg  13K
  financial-solutions.jpg  101K
  hero-bg.jpg  101K
  logo1.png  2K
  logo2.png  4K
  logo3.png  1K
  logo4.png  2K
  logo6.png  1K
  portfolio-thumb-1.jpg  85K
  portfolio-thumb-2.jpg  20K
  portfolio-thumb-3.jpg  19K
  portfolio-thumb-4.jpg  127K
  portfolio-thumb-5.jpg  135K
  portfolio-thumb-6.jpg  44K
  portfolio-thumb-7.jpg  125K
  portfolio-thumb-8.jpg  18K
  post-thumb-1.jpg  37K
  post-thumb-2.jpg  60K
  post-thumb-3.jpg  35K
  post-thumb-4.jpg  219K
public/frontend/js/
  jquery-1.11.0.min.js  94K [BIG]
  plugins.js  235K [BIG]
  script.js  6K
public/images/
  favicon.ico  3K
public/images/brand/
  brand-01.svg  1K
  brand-02.svg  725B
  brand-03.svg  836B
  brand-04.svg  3K
  brand-05.svg  1K
  brand-06.svg  684B
  brand-07.svg  1K
  brand-08.svg  2K
  brand-09.svg  892B
  brand-10.svg  2K
  brand-11.svg  1K
  brand-12.svg  1K
  brand-13.svg  1K
  brand-14.svg  1K
  brand-15.svg  1K
public/images/country/
  country-01.svg  2K
  country-02.svg  680B
  country-03.svg  1K
  country-04.svg  2K
  country-05.svg  758B
  country-06.svg  898B
  country-07.svg  874B
  country-08.svg  703B
public/images/error/
  404-dark.svg  2K
  404.svg  2K
public/images/grid-image/
  image-01.png  1222K
  image-02.png  321K
  image-03.png  473K
  image-04.png  204K
  image-05.png  147K
  image-06.png  80K
public/images/icons/
  file-image-dark.svg  1K
  file-image.svg  1K
  file-pdf-dark.svg  3K
  file-pdf.svg  3K
  file-video-dark.svg  1K
  file-video.svg  1K
public/images/logo/
  auth-logo.svg  17K
  logo-dark.svg  16K
  logo-icon.svg  9K
  logo.svg  16K
public/images/product/
  product-01.jpg  7K
  product-02.jpg  10K
  product-03.jpg  6K
  product-04.jpg  4K
  product-05.jpg  3K
public/images/shape/
  grid-01.svg  5K
public/images/user/
  owner.jpg  148K
  user-01.jpg  18K
  user-02.jpg  9K
  user-03.jpg  12K
  user-04.jpg  9K
  user-05.jpg  7K
  user-06.jpg  7K
  user-07.jpg  5K
  user-08.jpg  4K
  user-09.jpg  5K
  user-10.jpg  5K
  user-11.jpg  4K
  user-12.jpg  5K
  user-13.jpg  4K
  user-14.jpg  4K
  user-15.jpg  5K
  user-16.jpg  5K
  user-17.jpg  10K
  user-18.jpg  9K
  user-19.jpg  8K
  user-20.jpg  8K
  user-21.jpg  8K
  user-22.jpg  4K
  user-23.jpg  4K
  user-24.jpg  4K
  user-25.jpg  4K
  user-26.jpg  5K
  user-27.jpg  5K
  user-28.jpg  4K
  user-29.jpg  4K
  user-30.jpg  4K
  user-31.jpg  3K
  user-32.jpg  5K
  user-33.jpg  5K
  user-34.jpg  14K
  user-35.jpg  12K
  user-36.jpg  13K
  user-37.jpg  11K
public/images/video-thumb/
  thumb-16.png  118K
  youtube-icon-84.svg  691B
public/js/
  index.js  4K
public/js/components/
  calendar-init.js  10K
  image-resize.js  7K
  map-01.js  1K
public/js/components/charts/
  chart-01.js  1K
  chart-02.js  1K
  chart-03.js  1K
resources/css/
  app.css  16K
resources/data/
  banks-kenya.txt  1K
resources/design/
  tokens.cjs  2K
resources/design/emails/
  01-welcome-individual.html  5K
  02-welcome-business.html  5K
  03-verify-code.html  5K
  04-password-reset.html  5K
  05-new-sign-in.html  5K
  06-kyc-submitted.html  5K
  07-kyc-approved.html  5K
  08-kyc-action-needed.html  4K
  09-kyb-submitted.html  5K
  10-director-invite.html  4K
  11-kyb-approved.html  5K
  12-offer-ready.html  6K
  13-funds-sent.html  6K
  14-repayment-reminder.html  6K
  15-payment-received.html  6K
  16-payment-overdue.html  6K
  17-facility-closed.html  6K
  18-lc-issued.html  6K
  19-supplier-paid.html  6K
  20-investment-confirmed.html  6K
  21-payout-sent.html  5K
  README.txt  856B
  index.html  6K
resources/js/
  app.js  105B
  bootstrap.js  127B
  index.js  3K
resources/js/components/
  calendar-init.js  10K
  image-resize.js  7K
  map-01.js  1K
resources/js/components/charts/
  chart-01.js  1K
  chart-02.js  1K
  chart-03.js  1K
resources/views/
  dashboard.blade.php  7K
  maintenance.blade.php  2K
  welcome.blade.php  31K
resources/views/admin/system/
  _partner-funding.blade.php  3K
  simple.blade.php  74K [BIG]
resources/views/audit-log/
  index.blade.php  7K
resources/views/auth/
  confirm-password.blade.php  886B
  forgot-password.blade.php  2K
  login.blade.php  3K
  register.blade.php  9K
  reset-password.blade.php  4K
  verify-email.blade.php  1K
resources/views/business-reviews/
  _status.blade.php  369B
  index.blade.php  3K
  show.blade.php  13K
resources/views/cases/
  create.blade.php  8K
  edit.blade.php  6K
  index.blade.php  1K
  show.blade.php  37K
resources/views/components/
  application-logo.blade.php  2K
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
resources/views/components/az/
  alert.blade.php  903B
  confirm.blade.php  1K
  empty.blade.php  542B
  field.blade.php  1K
  money.blade.php  618B
  placeholder.blade.php  514B
  status-badge.blade.php  909B
  table.blade.php  985B
  timeline.blade.php  1K
resources/views/components/desk/
  dialog.blade.php  2K
resources/views/components/staff/
  alert.blade.php  1K
  confirm.blade.php  1K
  doc-drop.blade.php  3K
  empty.blade.php  915B
  field.blade.php  2K
  money.blade.php  791B
  placeholder.blade.php  714B
  status-badge.blade.php  1K
  table.blade.php  1K
  timeline.blade.php  1K
resources/views/disbursements/
  create.blade.php  3K
  edit.blade.php  3K
  index.blade.php  11K
  show.blade.php  0B
resources/views/emails/
  branded-text.blade.php  1K
  branded.blade.php  7K
  preview.blade.php  1K
resources/views/errors/
  403.blade.php  223B
  404.blade.php  164B
  419.blade.php  213B
  429.blade.php  190B
  500.blade.php  208B
  503.blade.php  216B
  layout.blade.php  3K
resources/views/home/
  asset-finance.blade.php  9K
  careers.blade.php  8K
  commodities.blade.php  8K
  company.blade.php  8K
  contact.blade.php  15K
  crb-solutions.blade.php  9K
  disclaimer.blade.php  6K
  emergency-solutions.blade.php  9K
  privacy.blade.php  6K
  services.blade.php  15K
  sitemap.blade.php  192B
  terms.blade.php  5K
resources/views/home/account/
  agreement.blade.php  1K
  borrow.blade.php  12K
  borrower.blade.php  12K
  extension.blade.php  5K
  help.blade.php  4K
  loan.blade.php  4K
  loans.blade.php  2K
  notifications.blade.php  3K
  partner.blade.php  6K
  plan.blade.php  3K
  profile.blade.php  5K
  statement-of-account.blade.php  5K
  statement.blade.php  3K
resources/views/home/account/partials/
  agreement-summary.blade.php  1K
  loan-state.blade.php  2K
resources/views/home/account/partner/
  earnings.blade.php  2K
  funds.blade.php  4K
  investment.blade.php  4K
  investments.blade.php  2K
  loan.blade.php  4K
  loans.blade.php  3K
  setup.blade.php  707B
  withdraw.blade.php  4K
resources/views/home/account/repay/
  _claims.blade.php  1K
  _payments.blade.php  1K
  closed.blade.php  2K
  none.blade.php  637B
  receipt.blade.php  3K
  show.blade.php  17K
resources/views/home/business/
  details.blade.php  5K
  documents.blade.php  7K
  financials.blade.php  4K
  index.blade.php  1K
  layout.blade.php  5K
  members.blade.php  7K
  review.blade.php  2K
  show.blade.php  4K
  start.blade.php  3K
  submitted.blade.php  1K
resources/views/home/business/partials/
  account-card.blade.php  1K
  status.blade.php  380B
resources/views/home/kyc/
  contacts.blade.php  4K
  documents.blade.php  14K
  income.blade.php  9K
  personal.blade.php  5K
  review.blade.php  2K
  submitted.blade.php  1K
resources/views/home/kyc/partials/
  saved.blade.php  431B
resources/views/home/layouts/
  account.blade.php  5K
  app.blade.php  905B
  kyc.blade.php  3K
resources/views/home/partials/
  alert-dialog.blade.php  1K
  footer.blade.php  6K
  head.blade.php  1K
  header.blade.php  1K
  toast.blade.php  355B
  verified-tick.blade.php  523B
resources/views/investments/
  index.blade.php  1K
  show.blade.php  46K [BIG]
resources/views/kyc-reviews/
  index.blade.php  3K
  show.blade.php  11K
resources/views/layouts/
  app.blade.php  16K
  frontend.blade.php  1K
  guest.blade.php  1K
  navigation.blade.php  4K
resources/views/loans/
  agreement.blade.php  0B
  assessment.blade.php  3K
  create.blade.php  57K [BIG]
  edit.blade.php  8K
  index.blade.php  1K
  report-pdf.blade.php  7K
  show.blade.php  40K [BIG]
resources/views/partials/
  breadcrumb.blade.php  998B
  calendar-event-modal.blade.php  13K
  common-grid-shape.blade.php  298B
  common-social-links.blade.php  4K
  datepicker.blade.php  1K
  header.blade.php  34K
  map-01.blade.php  4K
  media-card.blade.php  17K
  menu-icon.blade.php  27K
  overlay.blade.php  153B
  preloader.blade.php  371B
  sidebar-staff-desk.blade.php  3K
  sidebar.blade.php  38K
  top-card-group.blade.php  10K
  upcoming-schedule.blade.php  7K
  watchlist.blade.php  22K
resources/views/partials/alert/
  alert-error.blade.php  3K
  alert-info.blade.php  3K
  alert-success.blade.php  3K
  alert-warning.blade.php  3K
resources/views/partials/avatar/
  avatar-01.blade.php  1K
  avatar-02.blade.php  2K
  avatar-03.blade.php  2K
  avatar-04.blade.php  2K
resources/views/partials/badge/
  badge-01.blade.php  1K
  badge-02.blade.php  1K
  badge-03.blade.php  6K
  badge-04.blade.php  6K
  badge-05.blade.php  6K
  badge-06.blade.php  6K
resources/views/partials/buttons/
  button-01.blade.php  440B
  button-02.blade.php  2K
  button-03.blade.php  2K
  button-04.blade.php  662B
  button-05.blade.php  3K
  button-06.blade.php  3K
resources/views/partials/card/
  investments-stats-card.blade.php  4K
  loans-sats-card.blade.php  1K
  partners-stats-card.blade.php  4K
  users-card.blade.php  2K
resources/views/partials/chart/
  3layer-funnel.blade.php  2K
  active-vs-repaid.blade.php  901B
  amount-vs-balance.blade.php  0B
  average-days-late.blade.php  581B
  average-repayment-period.blade.php  607B
  cashflow-trend.blade.php  7K
  chart-01.blade.php  2K
  chart-02.blade.php  6K
  chart-03.blade.php  3K
  credit-score-distribution.blade.php  0B
  customer-balance.blade.php  7K
  days-late.blade.php  941B
  default-amounts.blade.php  0B
  default-breakdown.blade.php  0B
  delinquency-heatmap.blade.php  1K
  disbursement-amounts.blade.php  0B
  expected-repayments.blade.php  0B
  gender-distribution.blade.php  6K
  group-loan-status.blade.php  0B
  interest-penalties-brokerfees.blade.php  1K
  journal-entries.blade.php  0B
  loan-durations.blade.php  933B
  loan-funnel.blade.php  1K
  loan-status.blade.php  1K
  monthly-loans.blade.php  33K
  net-earnings.blade.php  571B
  outstanding-at-due.blade.php  1017B
  outstanding-balance.blade.php  1K
  portfolio-performance.blade.php  4K
  principal-vs-interest.blade.php  0B
  repayments-trend.blade.php  1017B
  report-chart.blade.php  4K
  requested-vs-disbursed.blade.php  5K
  role-distribution.blade.php  1K
  savings-balance.blade.php  1K
  status-distribution.blade.php  6K
resources/views/partials/grid/
  loan-sidebar-grid.blade.php  31K
resources/views/partials/grid-image/
  image-01.blade.php  344B
  image-02.blade.php  413B
  image-03.blade.php  625B
resources/views/partials/metric-group/
  metric-group-01.blade.php  25K
  metric-group-02.blade.php  9K
resources/views/partials/modal/
  alert-modal.blade.php  6K
  cases-create-modal.blade.php  53K [BIG]
  delete.blade.php  7K
  disbursement-create-modal.blade.php  37K
  error.blade.php  2K
  flash-alert.blade.php  705B
  investments-create-modal.blade.php  115K [BIG]
  investments-show-modal.blade.php  16K
  loan-cycles-modal.blade.php  13K
  loan-payment-plan-modal.blade.php  31K
  loan-progress-drawer.blade.php  9K
  loan-rollover-modal.blade.php  27K
  loans-create-modal.blade.php  56K [BIG]
  partners-create-modal.blade.php  31K
  partners-show-modal.blade.php  20K
  repayment-create-modal.blade.php  24K
  reports-create-modal.blade.php  6K
resources/views/partials/profile/
  profile-address-modal.blade.php  5K
  profile-info-modal.blade.php  9K
resources/views/partials/recovery/
  action-timeline.blade.php  8K
  case-action-modals.blade.php  10K
  priority-badge.blade.php  1K
  status-badge.blade.php  1K
resources/views/partials/table/
  table-06.blade.php  13K
  table-due-loans.blade.php  39K
  table-investments.blade.php  23K
  table-loans.blade.php  46K [BIG]
  table-partners.blade.php  25K
  table-recovery-cases.blade.php  19K
  table-reports-active-loans.blade.php  3K
  table-reports-cashflow.blade.php  2K
  table-reports-cic-reports.blade.php  6K
  table-reports-completed-loans.blade.php  3K
  table-reports-crb-report.blade.php  3K
  table-reports-customer-balance-summary.blade.php  3K
  table-reports-customer-reports.blade.php  3K
  table-reports-customer-savings.blade.php  2K
  table-reports-customer-statistics.blade.php  8K
  table-reports-defaulted-loans.blade.php  3K
  table-reports-expected-repayments.blade.php  3K
  table-reports-group-summary.blade.php  5K
  table-reports-journal-entries.blade.php  2K
  table-reports-loan-disbursement.blade.php  3K
  table-reports-loan-portfolio.blade.php  3K
  table-reports.blade.php  8K
  table-users.blade.php  85K [BIG]
resources/views/partners/
  index.blade.php  2K
  show.blade.php  29K
resources/views/profile/
  edit.blade.php  98K [BIG]
  show.blade.php  45K [BIG]
resources/views/profile/partials/
  delete-user-form.blade.php  2K
  update-password-form.blade.php  2K
  update-profile-information-form.blade.php  2K
resources/views/repayments/
  create.blade.php  2K
  edit.blade.php  2K
  index.blade.php  3K
  show.blade.php  0B
resources/views/reports/
  index.blade.php  14K
  show.blade.php  17K
resources/views/staff/desk/
  _ajax.blade.php  1K
  _header.blade.php  1K
  approvals.blade.php  7K
  collections.blade.php  5K
  disburse.blade.php  5K
  disbursement-queue.blade.php  3K
  end-of-day.blade.php  8K
  inbox.blade.php  3K
  partner-payouts.blade.php  2K
  payments.blade.php  6K [NEW]
  portfolio.blade.php  4K [NEW]
  products.blade.php  3K
  receive.blade.php  9K
  requests.blade.php  4K [NEW]
  roles.blade.php  4K
  sign-offs.blade.php  3K [NEW]
  wallets.blade.php  5K
resources/views/staff/operations-review/
  _repair-button.blade.php  485B [NEW]
  _repair-move.blade.php  920B [NEW]
  _repairs.blade.php  17K [NEW]
  index.blade.php  31K [NEW]
resources/views/stage2/
  components-customer.blade.php  5K
  components-staff.blade.php  6K
resources/views/users/
  create.blade.php  5K
  edit.blade.php  27K
  index.blade.php  262B
  show.blade.php  118K [BIG]
routes/
  auth.php  2K
  console.php  2K
  web.php  33K
scripts/
  borrower-states-demo.php  5K
  deploy-live.sh  4K
  pull-live-db.sh  2K [NEW]
  push-live.sh  1K
  repay-edge-demo.php  4K
  restore-test.sh  2K
  sync-tokens.cjs  1K
  walkthrough.sh  4K
storage/app/
  .gitignore  33B
storage/app/private/
  .gitignore  14B
storage/app/public/
  .gitignore  14B
storage/framework/
  .gitignore  119B
storage/framework/cache/
  .gitignore  21B
storage/framework/cache/data/
  .gitignore  14B
storage/framework/sessions/
  .gitignore  14B
storage/framework/testing/
  .gitignore  14B
storage/framework/views/
  .gitignore  14B
storage/logs/
  .gitignore  14B
tests/
  TestCase.php  926B
tests/Feature/
  AuditPruneTest.php  2K
  BrandedEmailsTest.php  3K
  BusinessKycTest.php  5K
  ExampleTest.php  359B
  KycEmailVerificationTest.php  2K
  KycFileAccessTest.php  2K
  KycSelfiesTest.php  4K
  LoanRejectionTest.php  3K
  LoanReopenCycleTest.php  3K
  LoanStatusByEditTest.php  3K
  LoansCheckIntegrityTest.php  4K
  PartnerFundingSettingsTest.php  5K
  PartnerLoanListsTest.php  3K
  PartnerWithdrawCapTest.php  3K
  PhaseZeroSmallFixesTest.php  2K
  ProfileTest.php  3K
  RepaymentCycleTest.php  2K
  UserDocumentUploadTest.php  3K
  WrittenOffBorrowerTest.php  2K
tests/Feature/Auth/
  AuthenticationTest.php  1K
  EmailVerificationTest.php  1K
  PasswordConfirmationTest.php  1K
  PasswordResetTest.php  1K
  PasswordUpdateTest.php  1K
  RegistrationTest.php  1K
tests/Feature/Money/
  ApprovalsTest.php  5K [NEW]
  AuditLoanFixtures.php  5K
  BorrowerMpesaTest.php  4K [NEW]
  CashUpTest.php  2K [NEW]
  CharacterisationTest.php  7K
  CreditCleanupTest.php  4K [NEW]
  CustomerEventsTest.php  3K [NEW]
  EngineV2Test.php  17K
  GraceFixTest.php  7K [NEW]
  LedgerTest.php  10K
  LedgerV2Test.php  4K
  LegacyChainsTest.php  5K [NEW]
  LoanRepairTest.php  6K [NEW]
  LoansRestateTest.php  4K
  MoneyColumnsTest.php  7K
  MpesaCallbackTest.php  7K [NEW]
  OperationsReviewTest.php  5K [NEW]
  OwnerDecisionsTest.php  3K [NEW]
  ProductVersionsTest.php  1K [NEW]
  RepaymentReversalTest.php  4K
  RepaymentSplitTest.php  10K [NEW]
  ReportsOnLedgerTest.php  2K [NEW]
  RequestsTest.php  4K [NEW]
  RolloverCleanupTest.php  8K [NEW]
  StaffPaymentsTest.php  4K [NEW]
  WriteOffRepaidTest.php  1K [NEW]
tests/Unit/
  CycleBalanceTest.php  7K
  ExampleTest.php  243B
```
