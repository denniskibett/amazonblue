<?php

namespace App\Console\Commands;

use App\Services\NplDetectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DetectNplLoans extends Command
{
    protected $signature = 'loans:detect-npl {--loan= : Check a specific loan ID}';
    protected $description = 'Detect non-performing loans and create recovery cases';

    protected $nplDetectionService;

    public function __construct(NplDetectionService $nplDetectionService)
    {
        parent::__construct();
        $this->nplDetectionService = $nplDetectionService;
    }

    public function handle()
    {
        $loanId = $this->option('loan');

        if ($loanId) {
            // Check specific loan
            $this->info("Checking loan #{$loanId}...");
            $result = $this->nplDetectionService->checkLoan($loanId);
            
            if (isset($result['error'])) {
                $this->error($result['error']);
                return 1;
            }

            $this->table(
                ['Field', 'Value'],
                [
                    ['Loan ID', $result['loan_id']],
                    ['Is NPL', $result['is_npl'] ? 'Yes' : 'No'],
                    ['Is Overdue', $result['is_overdue'] ? 'Yes' : 'No'],
                    ['Days Overdue', $result['days_overdue']],
                    ['NPL Threshold', $result['threshold']],
                    ['Status', $result['status']],
                ]
            );
            
            return 0;
        }

        // Run full detection
        $this->info('Starting NPL detection...');
        
        try {
            $results = $this->nplDetectionService->runDetection();

            $this->info('NPL Detection completed!');
            $this->newLine();
            $this->info('Summary:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Loans Checked', $results['loans_checked']],
                    ['New NPL Loans', $results['new_npl']],
                    ['New Overdue Loans', $results['new_overdue']],
                    ['Recovery Cases Created', $results['recovery_cases_created']],
                ]
            );

            if (!empty($results['errors'])) {
                $this->warn('Errors encountered:');
                foreach ($results['errors'] as $error) {
                    $this->error($error);
                }
            }

            Log::info('NPL Detection command completed', $results);

            return 0;

        } catch (\Exception $e) {
            $this->error('NPL Detection failed: ' . $e->getMessage());
            Log::error('NPL Detection command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}