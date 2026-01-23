<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanAgreementSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'section_type',
        'title',
        'content',
        'variables',
        'order',
        'is_editable',
        'status'
    ];

    protected $casts = [
        'content' => 'array',
        'variables' => 'array',
        'is_editable' => 'boolean'
    ];

    // Section types matching your document structure
    const TYPE_HEADER = 'header';
    const TYPE_PARTIES = 'parties';
    const TYPE_RECITALS = 'recitals';
    const TYPE_DEFINITIONS = 'definitions';
    const TYPE_LOAN_DETAILS = 'loan_details';
    const TYPE_REPAYMENT_TERMS = 'repayment_terms';
    const TYPE_PENALTIES = 'penalties';
    const TYPE_DOMICILIATION = 'domiciliation';
    const TYPE_EMERGENCY_TERMS = 'emergency_terms';
    const TYPE_RECOVERY = 'recovery';
    const TYPE_CONSENT = 'consent';
    const TYPE_CONFIDENTIALITY = 'confidentiality';
    const TYPE_DISCLAIMER = 'disclaimer';
    const TYPE_MODIFICATIONS = 'modifications';
    const TYPE_PRIOR_UNDERSTANDINGS = 'prior_understandings';
    const TYPE_WAIVER = 'waiver';
    const TYPE_NOTICE = 'notice';
    const TYPE_DISPUTE_RESOLUTION = 'dispute_resolution';
    const TYPE_FORCE_MAJEURE = 'force_majeure';
    const TYPE_APPLICABLE_LAWS = 'applicable_laws';
    const TYPE_ENTIRE_AGREEMENT = 'entire_agreement';
    const TYPE_SIGNATURES = 'signatures';

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function getFormattedContent(Loan $loan)
    {
        $content = $this->content ?? [];
        $variables = $this->variables ?? [];
        
        return $this->replaceVariables($content, $loan, $variables);
    }

    private function replaceVariables($content, Loan $loan, $variables)
    {
        $borrower = $loan->user;
        $borrowerProfile = $borrower->borrower;
        
        $variableMap = [
            '{{borrower_name}}' => strtoupper($borrower->name),
            '{{borrower_id_number}}' => $borrowerProfile->national_id ?? '______________',
            '{{loan_amount}}' => number_format($loan->amount, 2),
            '{{loan_amount_words}}' => $this->convertToWords($loan->amount),
            '{{interest_amount}}' => number_format($loan->amount * 0.1, 2), // 10% interest example
            '{{total_repayment}}' => number_format($loan->amount * 1.1, 2),
            '{{monthly_installment}}' => number_format(($loan->amount * 1.1) / 3, 2),
            '{{due_date}}' => $loan->due_date->format('jS \\d\\a\\y \\o\\f F Y'),
            '{{agreement_date}}' => $loan->created_at->format('jS \\d\\a\\y \\o\\f F Y'),
            '{{loan_purpose}}' => $loan->reason,
            '{{current_year}}' => date('Y'),
        ];

        // Add custom variables
        foreach ($variables as $key => $value) {
            $variableMap['{{' . $key . '}}'] = $value;
        }

        $replacedContent = [];
        foreach ($content as $line) {
            $replacedLine = $line;
            foreach ($variableMap as $variable => $replacement) {
                $replacedLine = str_replace($variable, $replacement, $replacedLine);
            }
            $replacedContent[] = $replacedLine;
        }

        return $replacedContent;
    }

    private function convertToWords($number)
    {
        // Simple number to words conversion
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];

        if ($number == 0) return 'Zero';

        $words = '';
        
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

        return trim($words);
    }
}