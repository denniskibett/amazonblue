<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanAgreementTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'content',
        'variables',
        'status'
    ];

    protected $casts = [
        'content' => 'array',
        'variables' => 'array'
    ];

    const TYPE_EMERGENCY = 'emergency';
    const TYPE_BUSINESS = 'business';
    const TYPE_PERSONAL = 'personal';

    public function generateAgreement(Loan $loan, $data = [])
    {
        $templateContent = $this->content;
        $variables = $this->variables ?? [];
        
        // Replace variables with actual data
        $agreementContent = $this->replaceVariables($templateContent, $loan, $data);
        
        return $agreementContent;
    }

    private function replaceVariables($content, Loan $loan, $data)
    {
        $borrower = $loan->borrower;
        $user = $loan->user;
        
        $variableMap = [
            '{{borrower_name}}' => $user->name,
            '{{borrower_id}}' => $user->id_number,
            '{{loan_amount}}' => number_format($loan->amount, 2),
            '{{loan_amount_words}}' => $this->convertToWords($loan->amount),
            '{{interest_rate}}' => $loan->loanType->interest_rate ?? 0,
            '{{due_date}}' => $loan->due_date->format('jS \\d\\a\\y \\o\\f F Y'),
            '{{agreement_date}}' => now()->format('jS \\d\\a\\y \\o\\f F Y'),
            '{{guarantor_name}}' => $loan->guarantor->name ?? 'N/A',
            '{{guarantor_id}}' => $loan->guarantor->id_number ?? 'N/A',
        ];

        // Add custom data variables
        foreach ($data as $key => $value) {
            $variableMap['{{' . $key . '}}'] = $value;
        }

        $replacedContent = $content;
        foreach ($variableMap as $variable => $replacement) {
            $replacedContent = str_replace($variable, $replacement, $replacedContent);
        }

        return $replacedContent;
    }

    private function convertToWords($number)
    {
        // Implementation for number to words conversion
        // You can use a package like "kwn/number-to-words"
        return "Kenya Shillings " . number_format($number) . " Only";
    }
}