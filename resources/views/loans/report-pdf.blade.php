<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Loan Report #{{ $loan->id }}</title>
    <style>
        @page { margin: 2cm; }
        body { font-family: DejaVu Sans, sans-serif; line-height: 1.6; }
        .header { border-bottom: 3px solid #333; padding-bottom: 15px; margin-bottom: 25px; }
        .badge { padding: 3px 10px; border-radius: 15px; font-size: 0.9em; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .section { margin-bottom: 25px; break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .timeline-bar { height: 4px; background: #eee; position: relative; margin: 20px 0; }
        .timeline-marker { position: absolute; top: -8px; width: 20px; height: 20px; border-radius: 50%; }
        .summary-box { background: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        <table width="100%">
            <tr>
                <td width="30%">
                    <img src="{{ storage_path('app/public/logo.png') }}" style="height: 50px;">
                </td>
                <td>
                    <h1 style="margin: 0; font-size: 24px;">Loan Report</h1>
                    <p style="margin: 5px 0; font-size: 14px; color: #666;">
                        Generated: {{ now()->format('M d, Y H:i') }}
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <!-- Loan Metadata -->
    <div class="section">
        <table>
            <tr>
                <td width="33%">
                    <strong>Loan ID:</strong> #{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }}<br>
                    <strong>Status:</strong> 
                    <span class="badge" style="background: 
                        @switch($loan->status)
                            @case('approved') #d4edda @break
                            @case('disbursed') #cce5ff @break
                            @case('rejected') #f8d7da @break
                            @default #e2e3e5
                        @endswitch">
                        {{ ucfirst($loan->status) }}
                    </span>
                </td>
                <td width="33%">
                    <strong>Borrower:</strong> {{ $loan->user->name }}<br>
                    <strong>Client Type:</strong> {{ $clientType == 0 ? 'Direct' : 'Brokered' }}
                </td>
                <td width="33%">
                    <strong>Loan Type:</strong> {{ $loan->loanType->name }}<br>
                    <strong>Period:</strong> {{ $period }} {{ $periodUnit }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Financial Summary -->
    <div class="section">
        <h3 style="color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 5px;">Financial Summary</h3>
        <div class="grid-3">
            <div class="summary-box">
                <div style="font-size: 1.2em; color: #27ae60; font-weight: bold;">
                    KES {{ number_format($principal, 2) }}
                </div>
                <div style="color: #7f8c8d;">Principal Amount</div>
            </div>
            <div class="summary-box">
                <div style="font-size: 1.2em; color: #2980b9; font-weight: bold;">
                    KES {{ number_format($interest, 2) }}
                </div>
                <div style="color: #7f8c8d;">Total Interest</div>
            </div>
            <div class="summary-box">
                <div style="font-size: 1.2em; color: {{ $netEarnings >= 0 ? '#27ae60' : '#c0392b' }}; font-weight: bold;">
                    KES {{ number_format($netEarnings, 2) }}
                </div>
                <div style="color: #7f8c8d;">Net Earnings</div>
            </div>
        </div>
    </div>

    <!-- Timeline Visualization -->
    <div class="section">
        <h3 style="color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 5px;">Payment Timeline</h3>
        <div class="timeline-bar">
            <div class="timeline-marker" style="left: 0%; background: #3498db;"></div>
            <div class="timeline-marker" style="left: 50%; background: #3498db;"></div>
            <div class="timeline-marker" style="left: {{ min($paymentProgress, 100) }}%; background: #2ecc71;"></div>
        </div>
        <table>
            <tr>
                <td width="33%">Disbursed: {{ $loan->borrow_date->format('M d, Y') }}</td>
                <td width="33%" style="text-align: center">Due: {{ $dueDate->format('M d, Y') }}</td>
                <td width="33%" style="text-align: right">Last Payment: {{ $lastRepaymentDate->format('M d, Y') }}</td>
            </tr>
        </table>
    </div>

    <!-- Repayment Schedule -->
    <div class="section">
        <h3 style="color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 5px;">Repayment Schedule</h3>
        <table>
            <thead style="background: #f8f9fa;">
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($repaymentSchedule as $payment)
                <tr>
                    <td>{{ $payment['date']->format('M d, Y') }}</td>
                    <td>{{ $payment['type'] }}</td>
                    <td>KES {{ number_format($payment['amount'], 2) }}</td>
                    <td>
                        <span style="color: {{ $payment['status']['color'] }};">
                            {{ $payment['status']['text'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Financial Breakdown -->
    <div class="section">
        <div class="grid-3">
            <div>
                <h4 style="color: #2c3e50; margin-bottom: 10px;">Income</h4>
                <table>
                    <tr><td>Principal</td><td>KES {{ number_format($principal, 2) }}</td></tr>
                    <tr><td>Interest</td><td>KES {{ number_format($interest, 2) }}</td></tr>
                    <tr><td>Penalties</td><td>KES {{ number_format($penaltyAmount, 2) }}</td></tr>
                </table>
            </div>
            <div>
                <h4 style="color: #2c3e50; margin-bottom: 10px;">Expenses</h4>
                <table>
                    <tr><td>Broker Fees</td><td>KES {{ number_format($brokerFees, 2) }}</td></tr>
                    <tr><td>Disbursements</td><td>KES {{ number_format($totalDisbursed, 2) }}</td></tr>
                </table>
            </div>
            <div>
                <h4 style="color: #2c3e50; margin-bottom: 10px;">Performance</h4>
                <table>
                    <tr><td>Repayment Rate</td><td>{{ $repaymentPercentage }}%</td></tr>
                    <tr><td>Days Delinquent</td><td>{{ $daysLate }}</td></tr>
                    <tr><td>Risk Rating</td><td>{{ $riskRating }}/10</td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div style="position: fixed; bottom: -50px; left: 0; right: 0; text-align: center; font-size: 0.8em; color: #666;">
        <hr style="border-top: 1px solid #ddd; margin-bottom: 10px;">
        {{ config('app.name') }} • Confidential Report • Page {{ $pageNumber }} of {{ $totalPages }}
    </div>
</body>
</html>