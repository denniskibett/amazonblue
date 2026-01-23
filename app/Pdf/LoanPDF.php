<?php

namespace App\Pdf;

use FPDF;
use Carbon\Carbon;

class LoanPDF extends FPDF
{
    private $loan;
    private $primaryColor = [41, 128, 185];  // Blue
    private $secondaryColor = [108, 117, 125]; // Gray
    private $accentColor = [40, 167, 69];     // Green
    private $warningColor = [255, 193, 7];    // Yellow

    public function __construct($loan)
    {
        parent::__construct('P', 'mm', 'A4');
        $this->loan = $loan;
    }

    // Header
    function Header()
    {
        // Logo - replace with your actual logo path
        $this->Image(public_path('images/logo.png'), 10, 6, 30);
        
        // Company Info
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 8, config('app.name'), 0, 1, 'R');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'G.P.O 50054 - 00100 Nairobi, Kenya', 0, 1, 'R');
        $this->Cell(0, 5, 'Phone: +(254) 456-7890 | Email: info@sharet.africa', 0, 1, 'R');
        $this->Cell(0, 5, 'Generated: ' . now()->format('d/m/Y H:i'), 0, 1, 'R');
        
        // Line break
        $this->Ln(10);
    }

    // Footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' | ' . config('app.url'), 0, 0, 'C');
    }

    // Loan Details Section
    public function loanDetails($disbursementDate, $dueDate, $daysLate, $lastRepaymentDate)
    {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Loan Agreement Statement', 0, 1);
        $this->Ln(5);

        // Two column layout
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(95, 8, 'Loan Information', 0, 0);
        $this->Cell(95, 8, 'Borrower Information', 0, 1);
        
        $this->SetFont('Arial', '', 10);
        $this->Cell(95, 6, 'Loan ID: #' . str_pad($this->loan->id, 5, '0', STR_PAD_LEFT), 0, 0);
        $this->Cell(95, 6, 'Name: ' . $this->loan->user->name, 0, 1);
        
        $this->Cell(95, 6, 'Principal: KES ' . number_format($this->loan->amount, 2), 0, 0);
        $this->Cell(95, 6, 'Contact: ' . ($this->loan->user->phone ?? 'N/A'), 0, 1);
        
        $this->Cell(95, 6, 'Interest Rate: ' . $this->loan->loanType->interest_rate . '%', 0, 0);
        $this->Cell(95, 6, 'Email: ' . ($this->loan->user->email ?? 'N/A'), 0, 1);
        
        $this->Cell(95, 6, 'Disbursement: ' . Carbon::parse($disbursementDate)->format('d M Y'), 0, 0);
        $this->Cell(95, 6, 'Client ID: ' . ($this->loan->user->id ?? 'N/A'), 0, 1);
        
        $this->Cell(95, 6, 'Due Date: ' . $dueDate->format('d M Y'), 0, 0);
        // Assuming $dueDate is already defined and is a Carbon instance
        if ($lastRepaymentDate instanceof \Carbon\Carbon && $dueDate instanceof \Carbon\Carbon) {
            $daysLate = $lastRepaymentDate->gt($dueDate) 
                ? $lastRepaymentDate->diffInDays($dueDate) 
                : 0;
        } else {
            $daysLate = 0; // or handle the error as needed
        }
        // Output the result
        $this->Cell(95, 6, 'Days Late: ' . $daysLate . ($daysLate === 1 ? ' day' : ' days'), 0, 1);
        // $this->Ln(10);
    }

    // Payment Schedule Table
    public function paymentSchedule($scheduleData)
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Payment Schedule', 0, 1);
        
        // Table header
        $this->SetFillColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        
        $this->Cell(30, 8, 'Due Date', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Principal', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Interest', 1, 0, 'C', true);
        $this->Cell(35, 8, 'Total', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Paid Date', 1, 0, 'C', true);
        $this->Cell(35, 8, 'Status', 1, 1, 'C', true);
        
        // Table data
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 10);
        
        foreach($scheduleData as $payment) {
            $this->Cell(30, 7, Carbon::parse($payment['date'])->format('d M Y'), 1, 0, 'C');
            $this->Cell(30, 7, 'KES ' . number_format($payment['principal'], 2), 1, 0, 'R');
            $this->Cell(30, 7, 'KES ' . number_format($payment['interest'], 2), 1, 0, 'R');
            $this->Cell(35, 7, 'KES ' . number_format($payment['total'], 2), 1, 0, 'R');
            $this->Cell(30, 7, !empty($payment['payment_date']) ? Carbon::parse($payment['payment_date'])->format('d M Y') : '-', 1, 0, 'C');
            
            // Status with color coding
            $status = $payment['status'] ?? 'Pending';
            if ($status === 'Paid') {
                $this->SetFillColor($this->accentColor[0], $this->accentColor[1], $this->accentColor[2]);
            } elseif ($status === 'Overdue') {
                $this->SetFillColor(220, 53, 69); // Red
            } else {
                $this->SetFillColor($this->warningColor[0], $this->warningColor[1], $this->warningColor[2]);
            }
            
            $this->SetTextColor(255);
            $this->Cell(35, 7, $status, 1, 1, 'C', true);
            $this->SetTextColor(0);
            $this->SetFillColor(255); // Reset
        }
        
        $this->Ln(10);
    }

    // Account Summary Section
    public function accountSummary($summaryData)
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Account Summary', 0, 1);
        
        $this->SetFont('Arial', '', 11);
        
        foreach($summaryData as $row) {
            $this->SetFillColor(240, 240, 240);
            $this->Cell(100, 8, $row['label'], 'TB', 0, 'L', true);
            $this->Cell(0, 8, $row['value'], 'TB', 1, 'R', true);
        }
        
        $this->Ln(10);
    }

    // Transaction History Table
    public function transactionHistory($transactions)
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Transaction History', 0, 1);
        
        // Table header
        $this->SetFillColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 11);
        
        $this->Cell(30, 8, 'Date', 1, 0, 'C', true);
        $this->Cell(50, 8, 'Description', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Debit', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Credit', 1, 0, 'C', true);
        $this->Cell(50, 8, 'Balance', 1, 1, 'C', true);
        
        // Table data
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 10);
        
        if ($transactions->isEmpty()) {
            $this->SetFont('Arial', 'I', 10);
            $this->Cell(0, 8, 'No transactions found', 1, 1, 'C');
        } else {
            foreach($transactions as $transaction) {
                $this->Cell(30, 7, Carbon::parse($transaction->date)->format('d M Y'), 1, 0, 'C');
                $this->Cell(50, 7, substr($transaction->description, 0, 25), 1, 0);
                $this->Cell(30, 7, $transaction->type === 'debit' ? 'KES ' . number_format($transaction->amount, 2) : '-', 1, 0, 'R');
                $this->Cell(30, 7, $transaction->type === 'credit' ? 'KES ' . number_format($transaction->amount, 2) : '-', 1, 0, 'R');
                $this->Cell(50, 7, 'KES ' . number_format($transaction->balance, 2), 1, 1, 'R');
            }
        }
    }
}