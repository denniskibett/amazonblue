<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;
use TCPDF;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Services\LoanCalculator;

class LoanAgreementService
{
    protected $signatureService;

    public function __construct()
    {
        $this->signatureService = new SignatureService();
    }

    public function generateLoanAgreement(Loan $loan)
    {
        $pdf = new TCPDF();
        
        // Set document information
        $pdf->SetCreator('Imagine-Nation Agency Limited');
        $pdf->SetAuthor('Imagine-Nation Agency Limited');
        $pdf->SetTitle('Emergency Loan Agreement - ' . $loan->id);
        $pdf->SetSubject('Emergency Loan Agreement');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        
        // Generate the agreement content
        $this->generateAgreementContent($pdf, $loan);
        
        $filename = "agreements/loan_agreement_{$loan->id}.pdf";
        $path = storage_path('app/public/' . $filename);
        
        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        $pdf->Output($path, 'F');
        
        return $path;
    }

    public function generateAgreementContent($pdf, Loan $loan)
    {
        $borrower = $loan->user;
        $borrowerProfile = $borrower->borrower;
        $loanOfficer = $loan->loanOfficer;
        $guarantor = $loan->guarantor;
        $adminWitness = User::where('role', 'admin')->orderBy('id', 'asc')->first();

        
        // Get admin witness (first admin user)
        $adminWitness = User::where('role', 'admin')->orderBy('id', 'asc')->first();
        
        // Get loan metrics
        $loanCalculator = new LoanCalculator();
        $metrics = $loanCalculator->calculateLoanMetrics($loan);
        
        // Get signatures
        // $borrowerSignaturePath = $this->getSignaturePath($borrower);
        $borrowerSignaturePath = $borrower ? $this->getSignaturePath($borrower) : null;
        
        $loanOfficerSignaturePath = $loanOfficer ? $this->getSignaturePath($loanOfficer) : null;
        $guarantorSignaturePath = $guarantor ? $this->getSignaturePath($guarantor) : null;
        $adminWitnessSignaturePath = $adminWitness ? $this->getSignaturePath($adminWitness) : null;

        // Calculate dates
        $borrowDate = Carbon::parse($loan->borrow_date);
        $disbursementDate = $loan->disbursements->first() ? 
            Carbon::parse($loan->disbursements->first()->disburse_date) : $borrowDate;
        $dueDate = $metrics['due_date'];
        $consentDate = $loan->consent_date ? Carbon::parse($loan->consent_date) : $borrowDate;

        // Set main font
        $pdf->SetFont('times', '', 12);

        // ===== PAGE 1 - TITLE PAGE (COVER) =====
        $pdf->AddPage();
        
        // Header section with dynamic dates - all in one line
        $pdf->SetFont('times', 'B', 14);
        $pdf->Cell(0, 10, 'DATED THIS ' . $borrowDate->format('jS \d\a\y \o\f F Y'), 0, 1, 'C');
        $pdf->Ln(20);

        $pdf->SetFont('times', 'B', 18);
        $pdf->Cell(0, 12, 'IMAGINE-NATION AGENCY LIMITED', 0, 1, 'C');
        $pdf->SetFont('times', '', 14);
        $pdf->Cell(0, 8, 'c/o AMAZONBLUE CAPITAL', 0, 1, 'C');
        $pdf->Ln(15);

        $pdf->SetFont('times', 'B', 16);
        $pdf->Cell(0, 10, 'and', 0, 1, 'C');
        $pdf->Ln(15);

        $pdf->SetFont('times', 'B', 16);
        $pdf->Cell(0, 10, strtoupper($borrower->name), 0, 1, 'C');
        $pdf->Ln(20);

        // Line before EMERGENCY AGREEMENT FOR SUPPLY
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(10);

        $pdf->SetFont('times', 'B', 18);
        $pdf->Cell(0, 12, 'EMERGENCY AGREEMENT FOR SUPPLY', 0, 1, 'C');
        $pdf->SetFont('times', 'B', 16);
        $pdf->Cell(0, 10, '-Of-', 0, 1, 'C');
        $pdf->SetFont('times', 'B', 18);
        $pdf->Cell(0, 12, 'FUNDS', 0, 1, 'C');

        // Line after FUNDS
        $pdf->Ln(10);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());

        // ===== PAGE 2 - AGREEMENT BODY (START PAGE NUMBERING) =====
        $pdf->AddPage();
        
        // Set page number starting from page 2
        $pdf->setPrintFooter(true);
        
        // Store the original Footer function and override it
        $pdf->footer = function($pdf) {
            $pdf->SetY(-15);
            $pdf->SetFont('times', 'I', 8);
            // Subtract 1 from page number to start from page 1 for content pages
            $pdf->Cell(0, 10, 'Page ' . ($pdf->PageNo() - 1), 0, 0, 'C');
        };

        $pdf->SetFont('times', 'B', 16);
        $pdf->Cell(0, 10, 'EMERGENCY LOAN AGREEMENT', 0, 1, 'C');
        $pdf->Ln(12);

        $pdf->SetFont('times', '', 12);
        $pdf->MultiCell(0, 6, 'THIS AGREEMENT is made on the ' . $borrowDate->format('jS \d\a\y \o\f F Y'), 0, 'L');
        $pdf->Ln(10);

        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 8, 'BETWEEN:', 0, 1, 'L');
        $pdf->Ln(6);

        // Lender information
        $pdf->SetFont('times', '', 12);
        $lenderText = '(1) IMAGINE-NATION AGENCY LIMITED c/o AMAZONBLUE CAPITAL of Post Office Box Number 50054-00100, NAIROBI, Kenya, and of Kenyan Company Registration Number PVT-MKUB6DM (hereinafter referred to as the <b>"Lender"</b> which expression shall where the context so admits include its personal representatives and assigns) of the one part';
        $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $lenderText, 0, 1);
        $pdf->Ln(8);

        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 8, 'AND', 0, 1, 'C');
        $pdf->Ln(6);

        // Borrower information with national_id
        $pdf->SetFont('times', '', 12);
        $borrowerAddress = $borrowerProfile->address ?? 'N/A';
        $idNumber = $borrowerProfile->national_id ?? ($borrowerProfile->id_number ?? 'N/A');
        
        $borrowerText = '(2) ' . strtoupper($borrower->name) . ' of ' . $borrowerAddress . ', NAIROBI, Kenya and of Kenyan National ID Number ' . $idNumber . ', (hereinafter referred to as the <b>"Borrower"</b> which expression shall where the context so admits include her personal representatives and assigns)';
        $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $borrowerText, 0, 1);
        $pdf->Ln(10);

        // WHEREAS clauses
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 8, 'WHEREAS:', 0, 1, 'L');
        $pdf->Ln(6);

        $pdf->SetFont('times', '', 12);
        $pdf->MultiCell(0, 6, 'A. The Borrower has requested for, and the Lender has agreed to grant, an emergency loan facility on the terms set out herein.', 0, 'L');
        $pdf->Ln(5);
        $pdf->MultiCell(0, 6, 'B. The borrower and the lender wish to evidence by this Agreement with the terms and conditions governing the said loan.', 0, 'L');
        $pdf->Ln(10);

        $pdf->SetFont('times', 'B', 12);
        $pdf->MultiCell(0, 8, 'NOW, THEREFORE, it is agreed as follows:', 0, 'L');
        $pdf->Ln(10);

        // Definitions with underlined bold headings
        $this->addSectionHeading($pdf, '1. DEFINITIONS');
        $pdf->Ln(6);

        $pdf->SetFont('times', '', 12);
        $def1 = '1.1 <b>"Lender"</b> shall mean "IMAGINE-NATION AGENCY LIMITED C/O AMAZONBLUE CAPITAL", being the party to this Agreement that advances the funds to the borrower under or in anticipation of this Agreement.';
        $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $def1, 0, 1);
        $pdf->Ln(5);

        $def2 = '1.2 <b>"Borrower"</b> shall mean "' . strtoupper($borrower->name) . '", being the party to this Agreement that requests for and receives the funds from the Lender.';
        $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $def2, 0, 1);
        $pdf->Ln(5);

        $def3 = '1.3 <b>"Parties"</b> means the parties to this Agreement.';
        $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $def3, 0, 1);
        $pdf->Ln(5);

        // Loan Disbursement and Purpose
        $this->addSectionHeading($pdf, '2. LOAN DISBURSEMENT AND PURPOSE');
        $pdf->Ln(6);

        $pdf->SetFont('times', '', 12);
        $currencyFull = 'Kenya Shillings';
        $currencyCode = 'KES';
        $loanAmountWords = $this->convertToWords($loan->amount);
        
        // Dynamic repayment terms based on loan type
        $repaymentDetails = $this->calculateRepaymentDetails($loan, $metrics, $borrowDate);
        
        $loanText = '2.1 The Lender agrees to advance to the Borrower an emergency loan in the sum of ' . $currencyFull . ' ' . $loanAmountWords . ' (' . $currencyCode . ' ' . number_format($loan->amount, 2) . ') (hereinafter referred to as the <b>"Loan"</b>).';
        $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $loanText, 0, 1);
        $pdf->Ln(6);

        $pdf->MultiCell(0, 6, '2.2 ' . $repaymentDetails['description'], 0, 'L');
        $pdf->Ln(5);

        foreach ($repaymentDetails['breakdown'] as $line) {
            $pdf->MultiCell(0, 6, $line, 0, 'L');
            $pdf->Ln(3);
        }
        $pdf->Ln(5);

        $aggregateText = 'Accordingly, the Borrower shall repay an aggregate total of ' . $currencyFull . ' ' . $this->convertToWords($repaymentDetails['total_repayment']) . ' (' . $currencyCode . ' ' . number_format($repaymentDetails['total_repayment'], 2) . '), inclusive of interest, within the agreed ' . $repaymentDetails['period_text'] . '.';
        $pdf->MultiCell(0, 6, $aggregateText, 0, 'L');
        $pdf->Ln(6);

        $totalRepaymentText = '2.3 The Borrower acknowledges that the total amount repayable under this Agreement shall therefore be ' . $currencyCode . ' ' . number_format($repaymentDetails['total_repayment'], 2) . ' (' . $currencyCode . ' ' . number_format($repaymentDetails['total_repayment'], 2) . ') (hereinafter referred to as the <b>"Total Repayment Amount"</b>).';
        $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $totalRepaymentText, 0, 1);
        $pdf->Ln(6);

        $pdf->MultiCell(0, 6, '2.4 The Borrower confirms and undertakes that the Loan shall be used exclusively for the purpose of: ' . ucwords($loan->reason ?? 'emergency financial needs'), 0, 'L');

        // Repayment Terms
        $this->addSectionHeading($pdf, '3. REPAYMENT TERMS');
        $pdf->Ln(6);

        $pdf->SetFont('times', '', 12);
        
        // Dynamic repayment schedule based on loan type
        $repaymentSchedule = $this->generateRepaymentSchedule($loan, $metrics, $borrowDate);
        foreach ($repaymentSchedule as $schedule) {
            // Use writeHTMLCell to render bold text
            $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $schedule, 0, 1);
            $pdf->Ln(4);
        }

        // Penalty for Default
        $this->addSectionHeading($pdf, '4. PENALTY FOR DEFAULT');
        $pdf->Ln(6);

        $pdf->SetFont('times', '', 12);
        $penaltyTerms = $this->generatePenaltyTerms($loan, $metrics, $borrowDate);
        foreach ($penaltyTerms as $term) {
            $pdf->MultiCell(0, 6, $term, 0, 'L');
            $pdf->Ln(4);
        }

        // Domiciliation of Proceeds
        $this->addSectionHeading($pdf, '5. DOMICILIATION OF PROCEEDS');
        $pdf->Ln(6);

        $pdf->SetFont('times', '', 12);
        $domiciliationTerms = $this->generateDomiciliationTerms($loan);
        foreach ($domiciliationTerms as $term) {
            $pdf->MultiCell(0, 6, $term, 0, 'L');
            $pdf->Ln(4);
        }

        // Continue with other sections
        $this->addStandardSections($pdf, $loan, $borrower, $loanOfficer);

        // ===== SIGNATURE PAGE =====
        $this->addSignaturesSection($pdf, $loan, $borrowerSignaturePath, $loanOfficerSignaturePath, $guarantorSignaturePath, $adminWitnessSignaturePath, $borrowDate, $adminWitness);
    }

    private function addSectionHeading($pdf, $title)
    {
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 8, $title, 'B', 1, 'L');
    }

    private function calculateRepaymentDetails($loan, $metrics, $borrowDate)
    {
        $currencyFull = 'Kenya Shillings';
        $currencyCode = 'KES';
        $loanType = $loan->loanType;
        $principal = $loan->amount;
        $interest = $metrics['interest'];
        $totalRepayment = $principal + $interest;
        
        // Calculate due dates based on borrow_date + loan type period
        $dueDate = $this->calculateDueDate($borrowDate, $loanType->period, $loanType->unit);
        
        $details = [
            'currency' => $currencyFull,
            'currency_code' => $currencyCode,
            'total_repayment' => $totalRepayment,
            'due_date' => $dueDate,
        ];

        // Check if loan type is days, weeks, or 1 month - use flat repayment
        if ($loanType->unit === 'days' || $loanType->unit === 'weeks' || 
            ($loanType->unit === 'months' && $loanType->period == 1)) {
            
            // Single payment for short-term loans (days, weeks, or 1 month)
            $details['description'] = 'The Loan shall attract a flat repayment of ' . $currencyCode . ' ' . number_format($totalRepayment, 2) . ' (' . $currencyCode . ' ' . number_format($totalRepayment, 2) . '). This repayment amount comprises:';
            $details['breakdown'] = [
                '- Principal: ' . $currencyCode . ' ' . number_format($principal, 2) . ' (' . $currencyCode . ' ' . number_format($principal, 2) . ')',
                '- Interest: ' . $currencyCode . ' ' . number_format($interest, 2) . ' (' . $currencyCode . ' ' . number_format($interest, 2) . ')',
                '- Total Repayment: ' . $currencyCode . ' ' . number_format($totalRepayment, 2) . ' (' . $currencyCode . ' ' . number_format($totalRepayment, 2) . ')'
            ];
            $details['period_text'] = $this->getPeriodText($loanType->period, $loanType->unit);
            $details['due_date_text'] = $dueDate->format('jS \d\a\y \o\f F Y');
            
        } else {
            // Monthly payments for longer-term loans (2 months and above)
            $monthlyPrincipal = $principal / $loanType->period;
            
            // Calculate monthly interest based on total interest divided by period
            $monthlyInterest = $interest / $loanType->period;
            
            $totalMonthlyRepayment = $monthlyPrincipal + $monthlyInterest;

            // Calculate monthly due dates
            $monthlyDueDates = [];
            for ($i = 1; $i <= $loanType->period; $i++) {
                $monthlyDueDates[] = $this->calculateDueDate($borrowDate, $i, 'months');
            }

            $details['description'] = 'The Loan shall attract a monthly repayment of ' . $currencyCode . ' ' . number_format($totalMonthlyRepayment, 2) . ' (' . $currencyCode . ' ' . number_format($totalMonthlyRepayment, 2) . ') for ' . $loanType->period . ' consecutive months. This repayment amount comprises:';
            $details['breakdown'] = [
                '- Monthly Principal: ' . $currencyCode . ' ' . number_format($monthlyPrincipal, 2) . ' (' . $currencyCode . ' ' . number_format($monthlyPrincipal, 2) . ')',
                '- Monthly Interest: ' . $currencyCode . ' ' . number_format($monthlyInterest, 2) . ' (' . $currencyCode . ' ' . number_format($monthlyInterest, 2) . ')',
                '- Total Monthly Repayment: ' . $currencyCode . ' ' . number_format($totalMonthlyRepayment, 2) . ' (' . $currencyCode . ' ' . number_format($totalMonthlyRepayment, 2) . ')'
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

    // Check if loan type is days, weeks, or 1 month - use flat repayment
    if ($loanType->unit === 'days' || $loanType->unit === 'weeks' || 
        ($loanType->unit === 'months' && $loanType->period == 1)) {
        
        // Single payment schedule
        $totalRepayment = $metrics['principal_plus_interest'];
        $dueDateText = $repaymentDetails['due_date_text'];
        
        $scheduleText = '3.1 The Borrower undertakes to repay the Loan in <b>one instalment</b> of ' . $currencyCode . ' <b>' . number_format($totalRepayment, 2) . ' (' . $currencyCode . ' ' . number_format($totalRepayment, 2) . ')</b>, falling due <b>on or before ' . $dueDateText . '</b>. An additional grace period of <b>zero (0) days</b> shall be allowed for settlement of the instalment. Any instalment remaining unpaid after its due date shall attract penalties as stipulated under <b>Clause 4</b> of this Agreement.';
        $schedule[] = $scheduleText;
    } else {
        // Monthly payment schedule for 2 months and above
        $monthlyPayment = $repaymentDetails['monthly_payment'];
        
        // Build monthly due dates text
        $dueDatesText = [];
        foreach ($repaymentDetails['monthly_due_dates'] as $index => $dueDate) {
            $dueDatesText[] = ($index + 1) . '. <b>' . $dueDate->format('jS \d\a\y \o\f F Y') . '</b>';
        }
        
        $schedule[] = '3.1 The Borrower undertakes to repay the Loan in ' . $loanType->period . ' equal monthly installments of ' . $currencyCode . ' <b>' . number_format($monthlyPayment, 2) . ' (' . $currencyCode . ' ' . number_format($monthlyPayment, 2) . ')</b> each, amounting to a total of ' . $currencyCode . ' ' . number_format($metrics['principal_plus_interest'], 2) . ' (' . $currencyCode . ' ' . number_format($metrics['principal_plus_interest'], 2) . '). The installments shall fall due <b>on or before</b> the following dates:';
        
        foreach ($dueDatesText as $dueDateText) {
            $schedule[] = '   ' . $dueDateText;
        }
        
        $schedule[] = 'An additional grace period of <b>zero (0) days</b> shall be allowed for settlement of each instalment. Any instalment remaining unpaid after its due date shall attract penalties as stipulated under <b>Clause 4</b> of this Agreement.';
    }

    $schedule[] = '3.2 Repayments shall be made primarily through direct bank transfer or M-Pesa to the Lender\'s designated account. The Borrower may, with the Lender\'s prior written consent, also settle installments through direct client payments to the Lender on the Borrower\'s behalf.';

    return $schedule;
}

    private function generatePenaltyTerms($loan, $metrics, $borrowDate)
    {
        $currencyCode = 'KES';
        $terms = [];
        
        $loanType = $loan->loanType;
        $repaymentDetails = $this->calculateRepaymentDetails($loan, $metrics, $borrowDate);

        // Check if loan type is days, weeks, or 1 month - use flat repayment
        if ($loanType->unit === 'days' || $loanType->unit === 'weeks' || 
            ($loanType->unit === 'months' && $loanType->period == 1)) {
            
            $penaltyAmount = $metrics['principal_plus_interest'] * 0.10; // 10% of total
            $terms[] = '4.1 In the event of failure to repay the Loan within the stipulated ' . $this->getPeriodText($loanType->period, $loanType->unit) . ' period, the Borrower shall be liable to pay a penalty interest equivalent to ten percent (10%) of the total repayment amount of (' . $currencyCode . ' ' . number_format($penaltyAmount, 2) . ') per day or the pending amount before the due date, accruing and compounding daily on the outstanding balance until full repayment is made.';
        } else {
            // For monthly payments (2 months and above)
            $monthlyPayment = $repaymentDetails['monthly_payment'];
            $penaltyAmount = $monthlyPayment * 0.10; // 10% of monthly installment
            $terms[] = '4.1 In the event of failure to repay any monthly installment within the stipulated ' . $loanType->period . ' month period, the Borrower shall be liable to pay a penalty interest equivalent to ten percent (10%) of the missed monthly installment amount of (' . $currencyCode . ' ' . number_format($penaltyAmount, 2) . ') per day or the pending amount before the due date, accruing and compounding daily on the outstanding balance until full repayment is made.';
        }

        $terms[] = '4.2 The penalty shall accrue daily and compound on the unpaid balance.';

        return $terms;
    }

    private function generateDomiciliationTerms($loan)
    {
        $terms = [];
        
        $loanType = $loan->loanType;

        $paymentFrequency = ($loanType->unit === 'days' || $loanType->unit === 'weeks' || 
                           ($loanType->unit === 'months' && $loanType->period == 1)) ? 'the' : 'monthly';

        $terms[] = '5.1 The Borrower hereby irrevocably authorizes and undertakes that all repayments under this Agreement shall be made directly from their personal bank account to the Lender\'s designated bank account until full repayment of the Loan and any applicable penalties or costs.';
        $terms[] = '5.2 The Borrower agrees to complete and provide their personal bank account details for purposes of effecting ' . $paymentFrequency . ' check-off payments to the Lender.';
        $terms[] = '5.3 For the avoidance of doubt, the Borrower undertakes to:';
        $terms[] = '   A. Ensure that sufficient funds are maintained in their designated bank account to cover each instalment on or before the due date;';
        
        if (!($loanType->unit === 'days' || $loanType->unit === 'weeks' || 
             ($loanType->unit === 'months' && $loanType->period == 1))) {
            $terms[] = '   B. Authorize their bank to process direct deductions (check-off) in favour of the Lender as per the repayment schedule; and';
        } else {
            $terms[] = '   B. Authorize their bank to process direct deductions (check-off) in favour of the Lender for the full repayment amount; and';
        }
        
        $terms[] = '   C. Cooperate fully in ensuring uninterrupted remittance of funds to the Lender and shall not interfere with, hinder, or obstruct such processes.';
        $terms[] = '5.4 All repayments shall be made into the following account of the Lender:';
        $terms[] = '   - Bank: I&M Bank';
        $terms[] = '   - Branch: Wilson';
        $terms[] = '   - Account Name: Imagine-Nation Agency Limited';
        $terms[] = '   - Account No.: 01104493106350';

        return $terms;
    }

    // ... (the rest of the methods remain the same as previous version)

    private function addStandardSections($pdf, $loan, $borrower, $loanOfficer)
    {
        // Continue with remaining standard sections from the original document
        $sections = [
            '6. EMERGENCY AND NON-NEGOTIABLE TERMS' => [
                '6.1 This loan is issued strictly as an emergency facility and not for development, investment, or business purposes.',
                '6.2 The terms of this Agreement are non-negotiable once the Loan has been disbursed and shall remain binding unless and until the Loan and all related sums, not limited to interest, have been fully paid.'
            ],
            '7. RECOVERY PROCESS' => [
                '7.1 Upon default by the Borrower, the Lender shall initiate immediate recovery proceedings, including but not limited to direct enforcement of check-off instructions with the Borrower\'s bank, and where necessary, attachment and auction of the Borrower\'s personal property.',
                '7.2 The Borrower shall be liable for all reasonable costs incurred by the Lender in connection with such recovery, including but not limited to legal fees, bank charges, and debt recovery agent fees.',
                '7.3 The Lender reserves the right to recover the outstanding amounts from any other lawful income sources belonging to the Borrower, including but not limited to his salaries, commissions, business earnings, or other bank accounts, until the Loan and any applicable penalties are fully settled.'
            ],
            '8. CONSENT' => [
                '8.1 The Borrower hereby consents to the sharing of this Agreement with third parties involved in payment processing, debt recovery, or enforcement actions.',
                '8.2 The Borrower acknowledges that failure to comply with the terms herein may negatively affect their creditworthiness, reputation, and may be subject to further legal action.'
            ],
            '9. CONFIDENTIALITY' => [
                '9.1 Each party shall treat all non-public, proprietary, or confidential information obtained in connection with this Agreement as strictly confidential and shall not disclose such information to any third party without the prior written consent of the disclosing party, unless required by law.',
                '9.2 This clause shall survive the termination or expiry of this Agreement for a period of ten (10) years from the date of termination.'
            ],
            '10. DISCLAIMER' => [
                '10.1 Nothing contained in this Agreement or in any confidential information constitutes any express or implied warranty of any kind. All representations or warranties, whether express or implied, including fitness for a particular purpose, merchantability, title, and non-infringement, are hereby disclaimed.',
                '10.2 Neither this Agreement nor any confidential information shall create, nor shall it be deemed to create, a legally binding or enforceable Agreement or offer to enter into any business relationship.'
            ],
            '11. MODIFICATIONS' => [
                'This Agreement may be modified only by a contract in writing executed by the party to this Agreement against whom enforcement of such modification is sought.'
            ],
            '12. PRIOR UNDERSTANDINGS' => [
                'This Agreement contains the entire agreement between the parties to this Agreement with respect to the subject matter of the Agreement and supersedes all negotiations, stipulations, understanding, agreements, representations and warranties if any, with respect to such subject matter, which precede or accompany the execution of this Agreement.'
            ],
            '13. WAIVER' => [
                'Any waiver of a default under this Agreement must be made in writing and shall not be a waiver of any other default concerning the same or any other provision of this Agreement. No delay or omission in the exercise of any right or remedy shall impair such right or remedy or be constructed as a waiver. A consent to or approval of any act shall not be deemed to waive or render unnecessary consent to or approval of any other or subsequent act.'
            ],
            '14. NOTICE' => [
                'All notice or other communication required or permitted to be served on any party by the other under this Agreement shall be in writing and shall be delivered by hand or email to the person to which it is required to be given at such person\'s address specified below or to such other address as they may have notified to the other party in writing. Notice shall be deemed received after 1 working day if by email or hand delivery.'
            ],
            '15. DISPUTE RESOLUTION' => [
                '15.1 In the event of any dispute arising from or in connection with this Agreement, the parties shall first attempt to resolve the matter amicably through good faith negotiations. If such negotiations fail to resolve the dispute within fourteen (14) days, the matter shall be referred to Mediation, to be conducted within a further fourteen (14) days, under the guidance of a Mediator mutually agreed upon by both parties.',
                '15.2 Should Mediation fail to produce a resolution, the Lender may enforce repayment through lawful recovery measures against the Borrower\'s personal assets, bank accounts, salaries, or other sources of income, subject to applicable laws.',
                '15.3 If no resolution is reached through the above mechanisms, either party may refer the dispute to a court of competent jurisdiction in Kenya, which shall be treated as the forum of last resort.'
            ],
            '16. FORCE MAJEURE' => [
                '16.1 No party shall be liable to other for non-performance of its obligation under its Agreement in case of force majeure, subject to immediate notification to the other party of the force majeure circumstances. Performance of this Agreement shall be suspended in the period of time during which the circumstances remain. The other party shall have the right to terminate this Agreement in the force majeure circumstance continues for a period of more than three months after the Notice by the party pleading force majeure.',
                '16.2 For the avoidance of doubt; incidences of force majeure shall include without limitation, Acts of God, lockdowns, riots and civil commotion, election violence, war and hostilities or the threat or apprehension thereof, embargoes and decrees by government or regulatory authorities that may stall the operations of either of the parties.',
                '16.3 Covid 19 and its variants shall not form part of this clause or exception.'
            ],
            '17. APPLICABLE LAWS' => [
                'This Emergency Loan Agreement shall be governed by and construed with the Laws of Kenya.'
            ],
            '18. ENTIRE AGREEMENT' => [
                '18.1 This Agreement constitutes the entire understanding between the parties and supersedes all prior agreements, representations, or negotiations.',
                '18.2 No amendment or variation to this Agreement shall be valid unless reduced to writing and signed by both parties.'
            ]
        ];

        foreach ($sections as $title => $content) {
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
            }
            
            $this->addSectionHeading($pdf, $title);
            $pdf->Ln(6);

            $pdf->SetFont('times', '', 12);
            foreach ($content as $line) {
                // Make quoted terms bold using HTML
                $line = preg_replace('/"([^"]*)"/', '<b>"$1"</b>', $line);
                $pdf->writeHTMLCell(0, 6, $pdf->GetX(), $pdf->GetY(), $line, 0, 1);
                $pdf->Ln(4);
            }
            $pdf->Ln(8);
        }

        // Contact information
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
        $pdf->MultiCell(0, 6, 'Imagine-Nation Agency Limited c/o Amazonblue Capital', 0, 'L');
        $pdf->MultiCell(0, 6, 'P.O. Box 50054-00100', 0, 'L');
        $pdf->MultiCell(0, 6, 'Nairobi', 0, 'L');
        $pdf->MultiCell(0, 6, 'Email: info@imaginenation.co.ke', 0, 'L');
        
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

    private function addSignaturesSection($pdf, $loan, $borrowerSignaturePath, $loanOfficerSignaturePath, $guarantorSignaturePath, $adminWitnessSignaturePath, $borrowDate, $adminWitness)
    {
        $pdf->AddPage();
        
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 10, 'IN WITNESS WHEREOF the parties have hereunto set their hands the date hereinbefore stated.', 0, 1, 'C');
        $pdf->Ln(20);

        // === LENDER SECTION ===
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 8, 'SIGNED by:', 0, 1, 'L');
        $pdf->Ln(8);

        $pdf->SetFont('times', '', 11);
        $pdf->Cell(0, 6, 'For and on behalf of IMAGINE-NATION AGENCY LIMITED', 0, 1, 'L');
        $pdf->Ln(12);

        $loanOfficer = $loan->loanOfficer;
        $adminName = $adminWitness ? $adminWitness->name : '................................';
        $loanOfficerName = $loanOfficer ? $loanOfficer->name : '................................';

        // Lender line with Name, Signature, and Date
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

        // Witness line with Name, Signature, and Date
        $pdf->SetFont('times', '', 11);
        $pdf->Cell(30, 6, 'Witness:', 0, 0, 'L');
        $pdf->SetFont('times', 'B', 11);
        $pdf->Cell(50, 6, $adminName, 0, 0, 'L');
        $pdf->SetFont('times', '', 11);
        $pdf->Cell(25, 6, 'Signature:', 0, 0, 'L');
        $pdf->Cell(40, 6, '................................', 0, 0, 'L');
        $pdf->Cell(15, 6, 'Date:', 0, 0, 'L');
        $pdf->SetFont('times', 'B', 11);
        $pdf->Cell(0, 6, $borrowDate->format('d/m/Y'), 0, 1, 'L');
        $pdf->Ln(20);

        // Add lender and witness signatures if available
        if ($loanOfficerSignaturePath && file_exists($loanOfficerSignaturePath)) {
            $pdf->Image($loanOfficerSignaturePath, 125, $pdf->GetY() - 46, 20, 15);
        }
        if ($adminWitnessSignaturePath && file_exists($adminWitnessSignaturePath)) {
            $pdf->Image($adminWitnessSignaturePath, 125, $pdf->GetY() - 35, 20, 15);
        }

        // === BORROWER SECTION ===
        $pdf->Ln(25);
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 8, 'SIGNED by:', 0, 1, 'L');
        $pdf->Ln(12);

        $borrower = $loan->user;
        $borrowerProfile = $borrower->borrower;
        $idNumber = $borrowerProfile->national_id ?? ($borrowerProfile->id_number ?? '................................');
        $guarantor = $loan->guarantor;
        $guarantorName = $guarantor ? $guarantor->name : '................................';

        // Borrower line with Name, Signature, and Date
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

        // Witness line with Name, Signature, and Date
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

        // Add borrower and guarantor signatures if available
        if ($borrowerSignaturePath && file_exists($borrowerSignaturePath)) {
            $pdf->Image($borrowerSignaturePath, 125, $pdf->GetY() - 48, 20, 15);
        }
        if ($guarantor && $guarantorSignaturePath && file_exists($guarantorSignaturePath)) {
            $pdf->Image($guarantorSignaturePath, 125, $pdf->GetY() - 34, 20, 15);
        }

        // === END OF AGREEMENT ===
        $pdf->Ln(25);
        $pdf->SetFont('times', 'B', 14);
        $pdf->Cell(0, 10, '- END OF AGREEMENT -', 0, 1, 'C');
    }

private function getSignaturePath($user)
{
    if (!$user || !$user->signature) {
        return null;
    }

    // $user->signature now contains full relative path like 'images/signatures/filename.png'
    $path = $user->signature;

    // Use Storage to check if file exists
    return Storage::disk('public')->exists($path) 
        ? Storage::disk('public')->path($path) 
        : null;
}


    private function getPeriodText($period, $unit)
    {
        $units = [
            'days' => 'day' . ($period > 1 ? 's' : ''),
            'weeks' => 'week' . ($period > 1 ? 's' : ''),
            'months' => 'month' . ($period > 1 ? 's' : ''),
            'years' => 'year' . ($period > 1 ? 's' : '')
        ];
        
        return $period . ' ' . ($units[$unit] ?? $unit);
    }

    private function getDueDateText($period, $unit, $dueDate)
    {
        if ($unit === 'days') {
            $ordinal = $this->getOrdinal($period);
            return 'the ' . $ordinal . ' day from the date of disbursement';
        }
        
        return $dueDate->format('jS \d\a\y \o\f F Y');
    }

    private function getOrdinal($number)
    {
        $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
        if (($number % 100) >= 11 && ($number % 100) <= 13) {
            return $number . 'th';
        }
        return $number . $ends[$number % 10];
    }

    private function convertToWords($number)
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];

        if ($number == 0) return 'Zero';

        $words = '';
        
        // Handle millions
        if ($number >= 1000000) {
            $millions = floor($number / 1000000);
            $words .= $this->convertToWords($millions) . ' Million ';
            $number %= 1000000;
        }

        // Handle thousands
        if ($number >= 1000) {
            $thousands = floor($number / 1000);
            $words .= $this->convertToWords($thousands) . ' Thousand ';
            $number %= 1000;
        }

        // Handle hundreds
        if ($number >= 100) {
            $hundreds = floor($number / 100);
            $words .= $ones[$hundreds] . ' Hundred ';
            $number %= 100;
        }

        // Handle tens and ones
        if ($number >= 20) {
            $tensDigit = floor($number / 10);
            $words .= $tens[$tensDigit] . ' ';
            $number %= 10;
        } elseif ($number >= 10) {
            $words .= $teens[$number - 10] . ' ';
            $number = 0;
        }

        if ($number > 0) {
            $words .= $ones[$number] . ' ';
        }

        return trim($words) . ' Only';
    }
}