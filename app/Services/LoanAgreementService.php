<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;
use TCPDF;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\LoanCalculator;
use App\Support\PrivateFiles;

class LoanAgreementService
{
    protected $signatureService;

    public function __construct()
    {
        $this->signatureService = new SignatureService();
    }

    // Bump when the agreement wording changes; stored with each signed copy.
    public const TEMPLATE_VERSION = '2026-10-18';

    /**
     * The agreement PDF for this loan (absolute path).
     *
     * Once the borrower has consented, the PDF is generated ONCE and frozen: its sha256 hash, signing
     * time, signer IP / user agent (when $signer is the consent request) and template version are kept on
     * the loan, and every later call (staff view, download, borrower view) returns that same file.
     * Before consent it is an unsigned draft and is rebuilt from current data each time.
     */
    public function generateLoanAgreement(Loan $loan, ?Request $signer = null): string
    {
        if ($frozen = $this->storedPath($loan)) {
            return $frozen;
        }

        // one writer at a time, so two first views can't each freeze a different copy
        return DB::transaction(function () use ($loan, $signer) {
            DB::table('loans')->where('id', $loan->id)->lockForUpdate()->first(['id']);
            if ($frozen = $this->storedPath($loan)) {
                return $frozen;
            }

            return $this->render($loan, $signer);
        });
    }

    private function render(Loan $loan, ?Request $signer): string
    {
        $loan->refresh();
        $wasSigned = (bool) $loan->agreement_sha256;
        $pdf = new TCPDF();

        $pdf->SetCreator('AIMZONE VENTURES');
        $pdf->SetAuthor('AIMZONE VENTURES');
        $pdf->SetTitle('Emergency Loan Agreement - ' . $loan->id);
        $pdf->SetSubject('Emergency Loan Agreement');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        $this->generateAgreementContent($pdf, $loan);

        // private disk: served only through the borrower's account and the staff loan routes
        $relative = $loan->consent
            ? "agreements/loan_agreement_{$loan->id}_" . Str::random(16) . '.pdf'
            : "agreements/drafts/loan_agreement_{$loan->id}.pdf";
        $contents = $pdf->Output('', 'S');
        if (! Storage::disk(PrivateFiles::DISK)->put($relative, $contents)) {
            throw new \RuntimeException("Could not save the agreement for loan #{$loan->id}.");
        }

        if (! $loan->consent) {
            return Storage::disk(PrivateFiles::DISK)->path($relative);
        }

        if ($wasSigned) {
            // the frozen file has gone missing: rebuilt so the borrower still has a copy, original signing details kept
            Log::error("Signed agreement file for loan #{$loan->id} was missing; regenerated", ['old_hash' => $loan->agreement_sha256]);
        }
        $fields = [
            'agreement_path' => $relative,
            'agreement_sha256' => hash('sha256', $contents),
            'agreement_version' => self::TEMPLATE_VERSION,
        ];
        if (! $wasSigned) {
            $fields += [
                'agreement_signed_at' => $signer ? now() : ($loan->consent_date ?? now()),
                'agreement_signer_ip' => $signer?->ip(),
                'agreement_user_agent' => $signer ? Str::limit((string) $signer->userAgent(), 250, '') : null,
            ];
        }
        // straight to the table: no loan events, updated_at untouched
        DB::table('loans')->where('id', $loan->id)->update($fields);
        $loan->forceFill($fields)->syncOriginal();

        return Storage::disk(PrivateFiles::DISK)->path($relative);
    }

    /**
     * The frozen, signed PDF on file for this loan (absolute path), or null if there is none yet.
     */
    public function storedPath(Loan $loan): ?string
    {
        $row = DB::table('loans')->where('id', $loan->id)->first(['agreement_path', 'agreement_sha256']);

        return $row && $row->agreement_sha256 ? PrivateFiles::absolute($row->agreement_path) : null;
    }

    public function generateAgreementContent($pdf, Loan $loan)
    {
        $borrower = $loan->user;
        $borrowerProfile = $borrower?->borrower;
        $loanOfficer = $loan->loanOfficer;
        $guarantor = $loan->guarantor;

        // Company director (admin) – always used as the company witness on the Lender side
        $companyDirector = User::role('admin')->orderBy('id', 'asc')->first();

        $loanCalculator = new LoanCalculator();
        $metrics = $loanCalculator->calculateLoanMetrics($loan);

        $borrowerSignaturePath = $borrower ? $this->getSignaturePath($borrower) : null;
        $loanOfficerSignaturePath = $loanOfficer ? $this->getSignaturePath($loanOfficer) : null;
        $guarantorSignaturePath = $guarantor ? $this->getSignaturePath($guarantor) : null;
        $companyDirectorSignaturePath = $companyDirector ? $this->getSignaturePath($companyDirector) : null;

        $borrowDate = Carbon::parse($loan->borrow_date);
        $disbursementDate = $loan->disbursements->first()
            ? Carbon::parse($loan->disbursements->first()->disburse_date)
            : $borrowDate;
        $dueDate = $metrics['due_date'];
        $consentDate = $loan->consent_date ? Carbon::parse($loan->consent_date) : $borrowDate;

        $pdf->SetFont('times', '', 12);

        // ===== PAGE 1 - TITLE PAGE =====
        $pdf->AddPage();

        $pdf->SetFont('times', 'B', 14);
        $pdf->Cell(0, 10, 'DATED THIS ' . strtoupper($borrowDate->format('jS \d\a\y \o\f F Y')), 0, 1, 'C');
        $pdf->Ln(20);

        $pdf->SetFont('times', 'B', 18);
        $pdf->Cell(0, 12, 'AIMZONE VENTURES', 0, 1, 'C');
        $pdf->SetFont('times', '', 14);
        $pdf->Cell(0, 8, 'P.O. Box 50054-00100, Nairobi, Kenya', 0, 1, 'C');
        $pdf->SetFont('times', '', 12);
        $pdf->Cell(0, 6, 'Tel: 0701607959  |  Email: info@aimzoneventures.com', 0, 1, 'C');
        $pdf->Ln(15);

        $pdf->SetFont('times', 'B', 16);
        $pdf->Cell(0, 10, 'and', 0, 1, 'C');
        $pdf->Ln(15);

        $pdf->SetFont('times', 'B', 16);
        $pdf->Cell(0, 10, strtoupper($borrower->name), 0, 1, 'C');
        $pdf->Ln(20);

        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(10);

        $pdf->SetFont('times', 'B', 18);
        $pdf->Cell(0, 12, 'EMERGENCY LOAN AGREEMENT', 0, 1, 'C');
        $pdf->Ln(4);
        $pdf->SetFont('times', 'B', 14);
        $pdf->Cell(0, 10, '- FOR THE ADVANCE OF FUNDS -', 0, 1, 'C');

        $pdf->Ln(10);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());

        // ===== PAGE 2 - AGREEMENT BODY =====
        $pdf->AddPage();
        $pdf->setPrintFooter(true);

        $pdf->footer = function ($pdf) {
            $pdf->SetY(-15);
            $pdf->SetFont('times', 'I', 8);
            $pdf->Cell(0, 10, 'Page ' . ($pdf->PageNo() - 1), 0, 0, 'C');
        };

        $pdf->SetFont('times', 'B', 16);
        $pdf->Cell(0, 10, 'EMERGENCY LOAN AGREEMENT', 0, 1, 'C');
        $pdf->Ln(12);

        $pdf->SetFont('times', '', 12);
        $pdf->MultiCell(0, 6, 'THIS AGREEMENT is made on the ' . $borrowDate->format('jS \d\a\y \o\f F Y') . '.', 0, 'L');
        $pdf->Ln(8);

        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 8, 'BETWEEN:', 0, 1, 'L');
        $pdf->Ln(4);

        $pdf->SetFont('times', '', 12);
        $lenderText = '(1) <b>AIMZONE VENTURES</b>, of Post Office Box Number 50054-00100, Nairobi, Kenya (hereinafter referred to as the <b>"Lender"</b>, which expression shall where the context so admits include its successors-in-title, personal representatives and permitted assigns) of the one part;';
        $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $lenderText, 0, 1);
        $pdf->Ln(6);

        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 8, 'AND', 0, 1, 'C');
        $pdf->Ln(4);

        $pdf->SetFont('times', '', 12);
        $borrowerAddress = $borrowerProfile?->address ?? 'N/A';
        $idNumber = $borrowerProfile?->national_id ?? ($borrowerProfile?->id_number ?? 'N/A');

        $borrowerText = '(2) <b>' . strtoupper($borrower->name) . '</b>, of ' . $borrowerAddress . ', Nairobi, Kenya, and of Kenyan National Identity Card Number ' . $idNumber . ' (hereinafter referred to as the <b>"Borrower"</b>, which expression shall where the context so admits include the Borrower\'s personal representatives, administrators and permitted assigns) of the other part.';
        $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $borrowerText, 0, 1);
        $pdf->Ln(10);

        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 8, 'WHEREAS:', 0, 1, 'L');
        $pdf->Ln(4);

        $pdf->SetFont('times', '', 12);
        $pdf->MultiCell(0, 6, 'A. The Borrower has applied to the Lender for an emergency loan facility and the Lender has, on the terms and conditions set out herein, agreed to advance the same.', 0, 'L');
        $pdf->Ln(4);
        $pdf->MultiCell(0, 6, 'B. The Parties wish to record in writing the terms and conditions governing the said loan facility.', 0, 'L');
        $pdf->Ln(8);

        $pdf->SetFont('times', 'B', 12);
        $pdf->MultiCell(0, 8, 'NOW THEREFORE, in consideration of the mutual covenants herein contained, IT IS HEREBY AGREED as follows:', 0, 'L');
        $pdf->Ln(10);

        // 1. DEFINITIONS
        $this->addSectionHeading($pdf, '1. DEFINITIONS AND INTERPRETATION');
        $pdf->Ln(6);

        $pdf->SetFont('times', '', 12);
        $defs = [
            '1.1 <b>"Lender"</b> means AIMZONE VENTURES, being the party advancing the Loan to the Borrower under or in anticipation of this Agreement.',
            '1.2 <b>"Borrower"</b> means ' . strtoupper($borrower->name) . ', being the party requesting and receiving the Loan from the Lender.',
            '1.3 <b>"Loan"</b> means the principal sum advanced by the Lender to the Borrower under this Agreement, together with all interest, fees, penalties and other sums payable hereunder.',
            '1.4 <b>"Parties"</b> means the Lender and the Borrower, and "Party" shall be construed accordingly.',
            '1.5 <b>"Total Repayment Amount"</b> means the aggregate of the principal, interest and any other sums payable under this Agreement.',
            '1.6 Words importing the singular shall include the plural and vice versa; words importing any gender shall include all genders; and headings are for convenience only and shall not affect interpretation.',
        ];
        foreach ($defs as $d) {
            $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $d, 0, 1);
            $pdf->Ln(4);
        }

        // 2. LOAN DISBURSEMENT AND PURPOSE
        $this->addSectionHeading($pdf, '2. LOAN DISBURSEMENT AND PURPOSE');
        $pdf->Ln(6);

        $pdf->SetFont('times', '', 12);
        $currencyFull = 'Kenya Shillings';
        $currencyCode = 'KES';
        $loanAmountWords = $this->convertToWords($loan->amount);

        $repaymentDetails = $this->calculateRepaymentDetails($loan, $metrics, $borrowDate);

        $loanText = '2.1 The Lender hereby agrees to advance to the Borrower, and the Borrower hereby agrees to borrow from the Lender, an emergency loan in the principal sum of ' . $currencyFull . ' ' . $loanAmountWords . ' (' . $currencyCode . ' ' . number_format((float) $loan->amount, 2) . ') (the <b>"Loan"</b>).';
        $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $loanText, 0, 1);
        $pdf->Ln(6);

        $pdf->MultiCell(0, 6, '2.2 ' . $repaymentDetails['description'], 0, 'L');
        $pdf->Ln(4);

        foreach ($repaymentDetails['breakdown'] as $line) {
            $pdf->MultiCell(0, 6, $line, 0, 'L');
            $pdf->Ln(2);
        }
        $pdf->Ln(4);

        $aggregateText = '2.3 Accordingly, the Borrower shall repay an aggregate total of ' . $currencyFull . ' ' . $this->convertToWords($repaymentDetails['total_repayment']) . ' (' . $currencyCode . ' ' . number_format($repaymentDetails['total_repayment'], 2) . '), inclusive of interest, within the agreed period of ' . $repaymentDetails['period_text'] . '.';
        $pdf->MultiCell(0, 6, $aggregateText, 0, 'L');
        $pdf->Ln(6);

        $totalRepaymentText = '2.4 The Borrower acknowledges and agrees that the total amount repayable under this Agreement shall be ' . $currencyCode . ' ' . number_format($repaymentDetails['total_repayment'], 2) . ' (the <b>"Total Repayment Amount"</b>).';
        $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $totalRepaymentText, 0, 1);
        $pdf->Ln(6);

        $pdf->MultiCell(0, 6, '2.5 The Borrower undertakes that the Loan shall be applied exclusively towards: ' . ucwords($loan->reason ?? 'emergency financial needs') . '.', 0, 'L');
        $pdf->Ln(8);

        // 3. REPAYMENT TERMS
        $this->addSectionHeading($pdf, '3. REPAYMENT TERMS');
        $pdf->Ln(6);

        $pdf->SetFont('times', '', 12);
        $repaymentSchedule = $this->generateRepaymentSchedule($loan, $metrics, $borrowDate);
        foreach ($repaymentSchedule as $schedule) {
            $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $schedule, 0, 1);
            $pdf->Ln(4);
        }
        $pdf->Ln(4);

        // 4. PENALTY FOR DEFAULT
        $this->addSectionHeading($pdf, '4. PENALTY FOR DEFAULT');
        $pdf->Ln(6);

        $pdf->SetFont('times', '', 12);
        $penaltyTerms = $this->generatePenaltyTerms($loan, $metrics, $borrowDate);
        foreach ($penaltyTerms as $term) {
            $pdf->MultiCell(0, 6, $term, 0, 'L');
            $pdf->Ln(4);
        }
        $pdf->Ln(4);

        // 5. DOMICILIATION OF PROCEEDS
        $this->addSectionHeading($pdf, '5. DOMICILIATION OF PROCEEDS');
        $pdf->Ln(6);

        $pdf->SetFont('times', '', 12);
        $domiciliationTerms = $this->generateDomiciliationTerms($loan);
        foreach ($domiciliationTerms as $term) {
            $pdf->MultiCell(0, 6, $term, 0, 'L');
            $pdf->Ln(4);
        }
        $pdf->Ln(4);

        $this->addStandardSections($pdf, $loan, $borrower, $loanOfficer);

        $this->addSignaturesSection(
            $pdf,
            $loan,
            $borrowerSignaturePath,
            $loanOfficerSignaturePath,
            $guarantorSignaturePath,
            $companyDirectorSignaturePath,
            $borrowDate,
            $companyDirector,
            $guarantor
        );
    }

    private function addSectionHeading($pdf, $title)
    {
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
        }
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 8, $title, 'B', 1, 'L');
    }

    private function calculateRepaymentDetails($loan, $metrics, $borrowDate)
    {
        $currencyFull = 'Kenya Shillings';
        $currencyCode = 'KES';
        $loanType = $loan->loanType;
        $principal = (float) $loan->amount;
        $interest = (float) $metrics['interest'];
        $totalRepayment = $principal + $interest;

        $dueDate = $this->calculateDueDate($borrowDate, $loanType->period, $loanType->unit);

        $details = [
            'currency' => $currencyFull,
            'currency_code' => $currencyCode,
            'total_repayment' => $totalRepayment,
            'due_date' => $dueDate,
        ];

        if ($loanType->unit === 'days' || $loanType->unit === 'weeks' ||
            ($loanType->unit === 'months' && $loanType->period == 1)) {

            $details['description'] = 'The Loan shall attract a flat repayment of ' . $currencyCode . ' ' . number_format($totalRepayment, 2) . '. This repayment amount comprises:';
            $details['breakdown'] = [
                '- Principal: ' . $currencyCode . ' ' . number_format($principal, 2),
                '- Interest: ' . $currencyCode . ' ' . number_format($interest, 2),
                '- Total Repayment: ' . $currencyCode . ' ' . number_format($totalRepayment, 2),
            ];
            $details['period_text'] = $this->getPeriodText($loanType->period, $loanType->unit);
            $details['due_date_text'] = $dueDate->format('jS \d\a\y \o\f F Y');
        } else {
            $monthlyPrincipal = $principal / $loanType->period;
            $monthlyInterest = $interest / $loanType->period;
            $totalMonthlyRepayment = $monthlyPrincipal + $monthlyInterest;

            $monthlyDueDates = [];
            for ($i = 1; $i <= $loanType->period; $i++) {
                $monthlyDueDates[] = $this->calculateDueDate($borrowDate, $i, 'months');
            }

            $details['description'] = 'The Loan shall attract a monthly repayment of ' . $currencyCode . ' ' . number_format($totalMonthlyRepayment, 2) . ' for ' . $loanType->period . ' consecutive months. This repayment amount comprises:';
            $details['breakdown'] = [
                '- Monthly Principal: ' . $currencyCode . ' ' . number_format($monthlyPrincipal, 2),
                '- Monthly Interest: ' . $currencyCode . ' ' . number_format($monthlyInterest, 2),
                '- Total Monthly Repayment: ' . $currencyCode . ' ' . number_format($totalMonthlyRepayment, 2),
            ];
            $details['period_text'] = $this->getPeriodText($loanType->period, $loanType->unit);
            $details['monthly_payment'] = $totalMonthlyRepayment;
            $details['monthly_due_dates'] = $monthlyDueDates;
        }

        return $details;
    }

    private function calculateDueDate($borrowDate, $period, $unit)
    {
        $dueDate = clone $borrowDate;

        switch ($unit) {
            case 'days':
                $dueDate->addDays($period);
                break;
            case 'weeks':
                $dueDate->addWeeks($period);
                break;
            case 'months':
                $dueDate->addMonths($period);
                break;
            case 'years':
                $dueDate->addYears($period);
                break;
            default:
                $dueDate->addDays($period);
                break;
        }

        return $dueDate;
    }

    private function generateRepaymentSchedule($loan, $metrics, $borrowDate)
    {
        $currencyCode = 'KES';
        $schedule = [];

        $loanType = $loan->loanType;
        $repaymentDetails = $this->calculateRepaymentDetails($loan, $metrics, $borrowDate);

        if ($loanType->unit === 'days' || $loanType->unit === 'weeks' ||
            ($loanType->unit === 'months' && $loanType->period == 1)) {

            $totalRepayment = $metrics['principal_plus_interest'];
            $dueDateText = $repaymentDetails['due_date_text'];

            $schedule[] = '3.1 The Borrower undertakes to repay the Loan in <b>one (1) instalment</b> of ' . $currencyCode . ' <b>' . number_format($totalRepayment, 2) . '</b>, falling due <b>on or before ' . $dueDateText . '</b>. No grace period shall be allowed for settlement of the instalment. Any instalment remaining unpaid after its due date shall attract penalties as stipulated under <b>Clause 4</b> of this Agreement.';
        } else {
            $monthlyPayment = $repaymentDetails['monthly_payment'];

            $dueDatesText = [];
            foreach ($repaymentDetails['monthly_due_dates'] as $index => $dueDate) {
                $dueDatesText[] = ($index + 1) . '. <b>' . $dueDate->format('jS \d\a\y \o\f F Y') . '</b>';
            }

            $schedule[] = '3.1 The Borrower undertakes to repay the Loan in ' . $loanType->period . ' equal monthly instalments of ' . $currencyCode . ' <b>' . number_format($monthlyPayment, 2) . '</b> each, amounting in aggregate to ' . $currencyCode . ' ' . number_format($metrics['principal_plus_interest'], 2) . '. The instalments shall fall due <b>on or before</b> the following dates:';

            foreach ($dueDatesText as $dueDateText) {
                $schedule[] = '   ' . $dueDateText;
            }

            $schedule[] = 'No grace period shall be allowed for settlement of each instalment. Any instalment remaining unpaid after its due date shall attract penalties as stipulated under <b>Clause 4</b> of this Agreement.';
        }

        $schedule[] = '3.2 Repayments shall be made primarily through direct bank transfer or M-Pesa to the Lender\'s designated account. The Borrower may, with the Lender\'s prior written consent, also settle instalments through direct client payments to the Lender on the Borrower\'s behalf.';

        return $schedule;
    }

    private function generatePenaltyTerms($loan, $metrics, $borrowDate)
    {
        $currencyCode = 'KES';
        $terms = [];

        $loanType = $loan->loanType;
        $repaymentDetails = $this->calculateRepaymentDetails($loan, $metrics, $borrowDate);

        if ($loanType->unit === 'days' || $loanType->unit === 'weeks' ||
            ($loanType->unit === 'months' && $loanType->period == 1)) {

            $penaltyAmount = $metrics['principal_plus_interest'] * 0.10;
            $terms[] = '4.1 In the event of failure by the Borrower to repay the Loan within the stipulated ' . $this->getPeriodText($loanType->period, $loanType->unit) . ' period, the Borrower shall be liable to pay a penalty interest equivalent to ten per cent (10%) of the Total Repayment Amount, being ' . $currencyCode . ' ' . number_format($penaltyAmount, 2) . ' per day or part thereof, on the outstanding balance, accruing and compounding daily until full repayment is made.';
        } else {
            $monthlyPayment = $repaymentDetails['monthly_payment'];
            $penaltyAmount = $monthlyPayment * 0.10;
            $terms[] = '4.1 In the event of failure by the Borrower to repay any monthly instalment within the stipulated period, the Borrower shall be liable to pay a penalty interest equivalent to ten per cent (10%) of the missed monthly instalment, being ' . $currencyCode . ' ' . number_format($penaltyAmount, 2) . ' per day or part thereof, on the outstanding balance, accruing and compounding daily until full repayment is made.';
        }

        $terms[] = '4.2 The penalty shall accrue daily and compound on the unpaid balance until the outstanding amount is settled in full.';

        return $terms;
    }

    private function generateDomiciliationTerms($loan)
    {
        $terms = [];

        $loanType = $loan->loanType;

        $paymentFrequency = ($loanType->unit === 'days' || $loanType->unit === 'weeks' ||
            ($loanType->unit === 'months' && $loanType->period == 1)) ? 'the' : 'monthly';

        $terms[] = '5.1 The Borrower hereby irrevocably authorises and undertakes that all repayments under this Agreement shall be made directly from the Borrower\'s personal bank account to the Lender\'s designated bank account until full repayment of the Loan and any applicable penalties or costs.';
        $terms[] = '5.2 The Borrower agrees to complete and provide their personal bank account details for purposes of effecting ' . $paymentFrequency . ' check-off payments to the Lender.';
        $terms[] = '5.3 For the avoidance of doubt, the Borrower undertakes to:';
        $terms[] = '   A. Ensure that sufficient funds are maintained in the designated bank account to cover each instalment on or before the due date;';

        if (!($loanType->unit === 'days' || $loanType->unit === 'weeks' ||
            ($loanType->unit === 'months' && $loanType->period == 1))) {
            $terms[] = '   B. Authorise the Borrower\'s bank to process direct deductions (check-off) in favour of the Lender as per the repayment schedule; and';
        } else {
            $terms[] = '   B. Authorise the Borrower\'s bank to process direct deductions (check-off) in favour of the Lender for the full repayment amount; and';
        }

        $terms[] = '   C. Cooperate fully in ensuring uninterrupted remittance of funds to the Lender and shall not interfere with, hinder, or obstruct such processes.';
        $terms[] = '5.4 All repayments shall be made into the following account of the Lender:';
        $terms[] = '   - Bank: I&M Bank';
        $terms[] = '   - Branch: Wilson';
        $terms[] = '   - Account Name: Imagine-Nation Agency Limited';
        $terms[] = '   - Account No.: 01104493106350';

        return $terms;
    }

    private function addStandardSections($pdf, $loan, $borrower, $loanOfficer)
    {
        $sections = [
            '6. EMERGENCY AND NON-NEGOTIABLE TERMS' => [
                '6.1 This Loan is issued strictly as an emergency facility and shall not be applied towards development, investment or business purposes.',
                '6.2 The terms of this Agreement are non-negotiable once the Loan has been disbursed and shall remain binding unless and until the Loan and all related sums, including interest and penalties, have been paid in full.',
            ],
            '7. RECOVERY PROCESS' => [
                '7.1 Upon default by the Borrower, the Lender shall be entitled to initiate immediate recovery proceedings, including but not limited to direct enforcement of check-off instructions with the Borrower\'s bank, and where necessary, attachment and auction of the Borrower\'s personal property.',
                '7.2 The Borrower shall be liable for all reasonable costs incurred by the Lender in connection with such recovery, including but not limited to legal fees, bank charges and debt recovery agent fees.',
                '7.3 The Lender reserves the right to recover the outstanding amounts from any other lawful income sources belonging to the Borrower, including but not limited to salary, commissions, business earnings or other bank accounts, until the Loan and any applicable penalties are fully settled.',
            ],
            '8. CONSENT' => [
                '8.1 The Borrower hereby consents to the sharing of this Agreement with third parties involved in payment processing, debt recovery or enforcement actions.',
                '8.2 The Borrower acknowledges that failure to comply with the terms herein may negatively affect their creditworthiness and reputation, and may render them subject to further legal action.',
            ],
            '9. CONFIDENTIALITY' => [
                '9.1 Each Party shall treat all non-public, proprietary or confidential information obtained in connection with this Agreement as strictly confidential and shall not disclose such information to any third party without the prior written consent of the disclosing Party, unless required by law.',
                '9.2 This clause shall survive the termination or expiry of this Agreement for a period of ten (10) years from the date of termination.',
            ],
            '10. DISCLAIMER' => [
                '10.1 Nothing contained in this Agreement or in any confidential information constitutes any express or implied warranty of any kind. All representations or warranties, whether express or implied, including fitness for a particular purpose, merchantability, title and non-infringement, are hereby disclaimed.',
                '10.2 Neither this Agreement nor any confidential information shall create, nor shall it be deemed to create, a legally binding or enforceable agreement or offer to enter into any business relationship other than as expressly set out herein.',
            ],
            '11. MODIFICATIONS' => [
                'This Agreement may be modified only by a contract in writing executed by the Party against whom enforcement of such modification is sought.',
            ],
            '12. PRIOR UNDERSTANDINGS' => [
                'This Agreement contains the entire agreement between the Parties with respect to the subject matter hereof and supersedes all negotiations, stipulations, understandings, agreements, representations and warranties, if any, with respect to such subject matter which precede or accompany the execution of this Agreement.',
            ],
            '13. WAIVER' => [
                'Any waiver of a default under this Agreement must be made in writing and shall not be a waiver of any other default concerning the same or any other provision of this Agreement. No delay or omission in the exercise of any right or remedy shall impair such right or remedy or be construed as a waiver. A consent to or approval of any act shall not be deemed to waive or render unnecessary consent to or approval of any other or subsequent act.',
            ],
            '14. NOTICE' => [
                'All notices or other communications required or permitted to be served on any Party by the other under this Agreement shall be in writing and shall be delivered by hand or email to the person to whom it is required to be given at such person\'s address specified below, or to such other address as they may have notified to the other Party in writing. Notice shall be deemed received after one (1) working day if by email or hand delivery.',
            ],
            '15. DISPUTE RESOLUTION' => [
                '15.1 In the event of any dispute arising from or in connection with this Agreement, the Parties shall first attempt to resolve the matter amicably through good faith negotiations. If such negotiations fail to resolve the dispute within fourteen (14) days, the matter shall be referred to mediation, to be conducted within a further fourteen (14) days, under the guidance of a mediator mutually agreed upon by both Parties.',
                '15.2 Should mediation fail to produce a resolution, the Lender may enforce repayment through lawful recovery measures against the Borrower\'s personal assets, bank accounts, salary or other sources of income, subject to applicable laws.',
                '15.3 If no resolution is reached through the above mechanisms, either Party may refer the dispute to a court of competent jurisdiction in Kenya, which shall be treated as the forum of last resort.',
            ],
            '16. FORCE MAJEURE' => [
                '16.1 No Party shall be liable to the other for non-performance of its obligations under this Agreement in case of force majeure, subject to immediate notification to the other Party of the force majeure circumstances. Performance of this Agreement shall be suspended for the period during which the circumstances remain. The other Party shall have the right to terminate this Agreement if the force majeure circumstance continues for a period of more than three (3) months after the notice by the Party pleading force majeure.',
                '16.2 For the avoidance of doubt, incidences of force majeure shall include, without limitation, acts of God, lockdowns, riots and civil commotion, election violence, war and hostilities or the threat or apprehension thereof, embargoes and decrees by government or regulatory authorities that may stall the operations of either of the Parties.',
                '16.3 Covid-19 and its variants shall not form part of this clause or exception.',
            ],
            '17. APPLICABLE LAWS' => [
                'This Emergency Loan Agreement shall be governed by and construed in accordance with the Laws of Kenya.',
            ],
            '18. ENTIRE AGREEMENT' => [
                '18.1 This Agreement constitutes the entire understanding between the Parties and supersedes all prior agreements, representations or negotiations.',
                '18.2 No amendment or variation to this Agreement shall be valid unless reduced to writing and signed by both Parties.',
            ],
        ];

        foreach ($sections as $title => $content) {
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
            }

            $this->addSectionHeading($pdf, $title);
            $pdf->Ln(6);

            $pdf->SetFont('times', '', 12);
            foreach ($content as $line) {
                $line = preg_replace('/"([^"]*)"/', '<b>"$1"</b>', $line);
                $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $line, 0, 1);
                $pdf->Ln(4);
            }
            $pdf->Ln(6);
        }

        $this->addContactInformation($pdf, $loan, $borrower, $loanOfficer);
    }

    private function addContactInformation($pdf, $loan, $borrower, $loanOfficer)
    {
        if ($pdf->GetY() > 200) {
            $pdf->AddPage();
        }

        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 8, 'Lender:', 0, 1, 'L');
        $pdf->SetFont('times', '', 12);
        $pdf->MultiCell(0, 6, 'AIMZONE VENTURES', 0, 'L');
        $pdf->MultiCell(0, 6, 'P.O. Box 50054-00100', 0, 'L');
        $pdf->MultiCell(0, 6, 'Nairobi', 0, 'L');
        $pdf->MultiCell(0, 6, 'Email: info@aimzoneventures.com', 0, 'L');
        $pdf->MultiCell(0, 6, 'Tel: 0701607959', 0, 'L');

        $officerName = $loanOfficer ? $loanOfficer->name : 'Mr. Dennis Kibet';
        $pdf->MultiCell(0, 6, 'Attention: ' . $officerName, 0, 'L');

        $pdf->Ln(8);
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 8, 'Borrower:', 0, 1, 'L');
        $pdf->SetFont('times', '', 12);
        $pdf->MultiCell(0, 6, $borrower->name, 0, 'L');
        $pdf->MultiCell(0, 6, $borrower->borrower->address ?? 'N/A', 0, 'L');
        $pdf->MultiCell(0, 6, 'P.O. Box ' . ($borrower->borrower->p_o_box ?? 'N/A'), 0, 'L');
        $pdf->MultiCell(0, 6, 'Nairobi', 0, 'L');
        $pdf->MultiCell(0, 6, 'Email: ' . ($borrower->email ?? 'N/A'), 0, 'L');
        $pdf->MultiCell(0, 6, 'Attention: ' . $borrower->name, 0, 'L');
    }

    /**
     * Build the signature page.
     *
     * Rules:
     *  - Lender (business) is ALWAYS witnessed by the company director.
     *  - Borrower (client) is witnessed by the guarantor ONLY if a guarantor exists.
     *  - If no guarantor: borrower signs alone, with no witness row printed.
     */
    private function addSignaturesSection(
        $pdf,
        $loan,
        $borrowerSignaturePath,
        $loanOfficerSignaturePath,
        $guarantorSignaturePath,
        $companyDirectorSignaturePath,
        $borrowDate,
        $companyDirector,
        $guarantor
    ) {
        $pdf->AddPage();

        $pdf->SetFont('times', 'B', 12);
        $pdf->MultiCell(0, 10, 'IN WITNESS WHEREOF the Parties have hereunto set their hands the day and year first hereinbefore written.', 0, 'C');
        $pdf->Ln(20);

        // ==================== LENDER SECTION ====================
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 8, 'SIGNED by the LENDER:', 0, 1, 'L');
        $pdf->Ln(8);

        $pdf->SetFont('times', '', 11);
        $pdf->Cell(0, 6, 'For and on behalf of AIMZONE VENTURES', 0, 1, 'L');
        $pdf->Ln(12);

        $loanOfficer = $loan->loanOfficer;
        $directorName = $companyDirector ? $companyDirector->name : '................................';
        $loanOfficerName = $loanOfficer ? $loanOfficer->name : '................................';

        // Director / Authorised signatory
        $pdf->SetFont('times', '', 11);
        $pdf->Cell(30, 6, 'Name:', 0, 0, 'L');
        $pdf->SetFont('times', 'B', 11);
        $pdf->Cell(50, 6, $loanOfficerName, 0, 0, 'L');
        $pdf->SetFont('times', '', 11);
        $pdf->Cell(25, 6, 'Signature:', 0, 0, 'L');
        $pdf->Cell(40, 6, '................................', 0, 0, 'L');
        $pdf->Cell(15, 6, 'Date:', 0, 0, 'L');
        $pdf->SetFont('times', 'B', 11);
        $pdf->Cell(0, 6, $borrowDate->format('d/m/Y'), 0, 1, 'L');
        $pdf->Ln(10);

        // Company director as WITNESS on the Lender side (always present)
        $pdf->SetFont('times', '', 11);
        $pdf->Cell(30, 6, 'Witness:', 0, 0, 'L');
        $pdf->SetFont('times', 'B', 11);
        $pdf->Cell(50, 6, $directorName, 0, 0, 'L');
        $pdf->SetFont('times', '', 11);
        $pdf->Cell(25, 6, 'Signature:', 0, 0, 'L');
        $pdf->Cell(40, 6, '................................', 0, 0, 'L');
        $pdf->Cell(15, 6, 'Date:', 0, 0, 'L');
        $pdf->SetFont('times', 'B', 11);
        $pdf->Cell(0, 6, $borrowDate->format('d/m/Y'), 0, 1, 'L');
        $pdf->Ln(20);

        // Place lender + witness signature images
        if ($loanOfficerSignaturePath && file_exists($loanOfficerSignaturePath)) {
            $pdf->Image($loanOfficerSignaturePath, 125, $pdf->GetY() - 46, 20, 15);
        }
        if ($companyDirectorSignaturePath && file_exists($companyDirectorSignaturePath)) {
            $pdf->Image($companyDirectorSignaturePath, 125, $pdf->GetY() - 35, 20, 15);
        }

        // ==================== BORROWER SECTION ====================
        $pdf->Ln(25);
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 8, 'SIGNED by the BORROWER:', 0, 1, 'L');
        $pdf->Ln(12);

        $borrower = $loan->user;
        $borrowerProfile = $borrower->borrower;
        $idNumber = $borrowerProfile?->national_id ?? ($borrowerProfile?->id_number ?? '................................');

        // Borrower signature row
        $pdf->SetFont('times', '', 11);
        $pdf->Cell(30, 6, 'Name:', 0, 0, 'L');
        $pdf->SetFont('times', 'B', 11);
        $pdf->Cell(50, 6, $borrower->name, 0, 0, 'L');
        $pdf->SetFont('times', '', 11);
        $pdf->Cell(25, 6, 'Signature:', 0, 0, 'L');
        $pdf->Cell(40, 6, '................................', 0, 0, 'L');
        $pdf->Cell(15, 6, 'Date:', 0, 0, 'L');
        $pdf->SetFont('times', 'B', 11);
        $pdf->Cell(0, 6, $borrowDate->format('d/m/Y'), 0, 1, 'L');
        $pdf->Ln(8);

        if ($guarantor) {
            // ============ GUARANTOR = WITNESS (only when present) ============
            $guarantorName = $guarantor->name;

            $pdf->SetFont('times', '', 11);
            $pdf->Cell(30, 6, 'Witness:', 0, 0, 'L');
            $pdf->SetFont('times', 'B', 11);
            $pdf->Cell(50, 6, $guarantorName, 0, 0, 'L');
            $pdf->SetFont('times', '', 11);
            $pdf->Cell(25, 6, 'Signature:', 0, 0, 'L');
            $pdf->Cell(40, 6, '................................', 0, 0, 'L');
            $pdf->Cell(15, 6, 'Date:', 0, 0, 'L');
            $pdf->SetFont('times', 'B', 11);
            $pdf->Cell(0, 6, $borrowDate->format('d/m/Y'), 0, 1, 'L');
            $pdf->Ln(20);

            if ($borrowerSignaturePath && file_exists($borrowerSignaturePath)) {
                $pdf->Image($borrowerSignaturePath, 125, $pdf->GetY() - 48, 20, 15);
            }
            if ($guarantorSignaturePath && file_exists($guarantorSignaturePath)) {
                $pdf->Image($guarantorSignaturePath, 125, $pdf->GetY() - 34, 20, 15);
            }
        } else {
            // ============ NO GUARANTOR: borrower signs alone, no witness row ============
            if ($borrowerSignaturePath && file_exists($borrowerSignaturePath)) {
                $pdf->Image($borrowerSignaturePath, 125, $pdf->GetY() - 20, 20, 15);
            }
        }

        $pdf->Ln(25);
        $pdf->SetFont('times', 'B', 14);
        $pdf->Cell(0, 10, '- END OF AGREEMENT -', 0, 1, 'C');
    }

    private function getSignaturePath($user)
    {
        if (!$user || !$user->signature) {
            return null;
        }

        return PrivateFiles::absolute($user->signature);
    }

    private function getPeriodText($period, $unit)
    {
        $units = [
            'days' => 'day' . ($period > 1 ? 's' : ''),
            'weeks' => 'week' . ($period > 1 ? 's' : ''),
            'months' => 'month' . ($period > 1 ? 's' : ''),
            'years' => 'year' . ($period > 1 ? 's' : ''),
        ];

        return $period . ' ' . ($units[$unit] ?? $unit);
    }

    private function getOrdinal($number)
    {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if (($number % 100) >= 11 && ($number % 100) <= 13) {
            return $number . 'th';
        }
        return $number . $ends[$number % 10];
    }

    /**
     * Convert a numeric amount (which may be a decimal string) into words.
     * Handles the cents/decimals part so that e.g. 1.20 => "One and Twenty Cents Only".
     */
    private function convertToWords($number)
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];

        $number = round((float) $number, 2);
        $whole = (int) floor($number);
        $cents = (int) round(($number - $whole) * 100);

        if ($whole == 0 && $cents == 0) {
            return 'Zero';
        }

        $words = '';

        if ($whole > 0) {
            $words = $this->convertIntegerToWords($whole, $ones, $tens, $teens);
        }

        if ($cents > 0) {
            $centsWords = $this->convertIntegerToWords($cents, $ones, $tens, $teens);
            $words .= ($words !== '' ? ' and ' : '') . $centsWords . ' Cents';
        }

        return trim($words) . ' Only';
    }

    private function convertIntegerToWords($number, $ones, $tens, $teens)
    {
        $number = (int) $number;
        $words = '';

        if ($number >= 1000000) {
            $millions = intdiv($number, 1000000);
            $words .= $this->convertIntegerToWords($millions, $ones, $tens, $teens) . ' Million ';
            $number %= 1000000;
        }

        if ($number >= 1000) {
            $thousands = intdiv($number, 1000);
            $words .= $this->convertIntegerToWords($thousands, $ones, $tens, $teens) . ' Thousand ';
            $number %= 1000;
        }

        if ($number >= 100) {
            $hundreds = intdiv($number, 100);
            $words .= $ones[$hundreds] . ' Hundred ';
            $number %= 100;
        }

        if ($number >= 20) {
            $tensDigit = intdiv($number, 10);
            $words .= $tens[$tensDigit] . ' ';
            $number %= 10;
        } elseif ($number >= 10) {
            $words .= $teens[$number - 10] . ' ';
            $number = 0;
        }

        if ($number > 0) {
            $words .= $ones[$number] . ' ';
        }

        return trim($words);
    }
}