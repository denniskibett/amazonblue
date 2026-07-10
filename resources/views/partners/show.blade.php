{{-- resources/views/partners/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Back Button -->
    <div class="mb-4">
        <a href="{{ route('partners.index') }}" 
           class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Partners
        </a>
    </div>

    <!-- Partner Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <span class="text-2xl font-semibold text-blue-600 dark:text-blue-400">
                        {{ ucfirst(substr($partner->name, 0, 1)) }}
                    </span>
                </div>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $partner->name }}</h1>
                    <div class="flex flex-wrap items-center gap-2 mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                              :class="{
                                  'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': '{{ $partner->status }}' === 'active',
                                  'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': '{{ $partner->status }}' === 'inactive',
                                  'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': '{{ $partner->status }}' === 'suspended'
                              }">
                            {{ ucfirst($partner->status) }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                            {{ ucfirst($partner->type) }}
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $partner->email }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="window.dispatchEvent(new CustomEvent('edit-partner', { detail: { partner: @json($data) } }))" 
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 transition-colors shadow-lg shadow-blue-600/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Partner
                </button>
                <button @click="deletePartner({{ $partner->id }}, '{{ addslashes($partner->name) }}')" 
                        class="inline-flex items-center gap-2 rounded-lg bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 border border-red-300 dark:border-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Contribution</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white">KES {{ number_format($partner->total_contribution, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Current Balance</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white">KES {{ number_format($partner->current_balance, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Invested</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white">KES {{ number_format($partner->total_invested ?? 0, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Net Position</p>
            <p class="text-2xl font-semibold {{ ($partner->net_position ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                KES {{ number_format($partner->net_position ?? 0, 2) }}
            </p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Partner Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Partner Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Partner Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Full Name</p>
                        <p class="text-gray-900 dark:text-white">{{ $partner->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</p>
                        <p class="text-gray-900 dark:text-white">{{ $partner->email }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</p>
                        <p class="text-gray-900 dark:text-white">{{ $partner->phone ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Company</p>
                        <p class="text-gray-900 dark:text-white">{{ $partner->company_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Registration Number</p>
                        <p class="text-gray-900 dark:text-white">{{ $partner->registration_number ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tax ID</p>
                        <p class="text-gray-900 dark:text-white">{{ $partner->tax_id ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Associated User</p>
                        <p class="text-gray-900 dark:text-white">{{ $partner->user?->name ?? 'No User Assigned' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Joined</p>
                        <p class="text-gray-900 dark:text-white">{{ $partner->created_at?->format('F j, Y') ?? 'N/A' }}</p>
                    </div>
                </div>
                @if($partner->notes)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</p>
                    <p class="text-gray-900 dark:text-white mt-1">{{ $partner->notes }}</p>
                </div>
                @endif
            </div>

            <!-- Financial Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Financial Settings</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Profit Share Rate</p>
                        <p class="text-gray-900 dark:text-white">{{ $partner->profit_share_rate ?? 0 }}%</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Max LTV</p>
                        <p class="text-gray-900 dark:text-white">{{ $partner->max_loan_to_value ?? 75 }}%</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Risk Tolerance</p>
                        <p class="text-gray-900 dark:text-white">{{ ucfirst($partner->risk_tolerance ?? 'moderate') }}</p>
                    </div>
                </div>
            </div>

            <!-- Banking Details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Banking Details</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Account Name</p>
                        <p class="text-gray-900 dark:text-white">{{ $partner->bank_account_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Account Number</p>
                        <p class="text-gray-900 dark:text-white">{{ $partner->bank_account_number ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Bank Name</p>
                        <p class="text-gray-900 dark:text-white">{{ $partner->bank_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">SWIFT Code</p>
                        <p class="text-gray-900 dark:text-white">{{ $partner->swift_code ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Transaction Summary -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Quick Actions</h4>
                <div class="space-y-2">
                    <button @click="openContributionModal()" 
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Contribution
                    </button>
                    <button @click="openWithdrawModal()" 
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-yellow-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-yellow-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                        </svg>
                        Process Withdrawal
                    </button>
                    <button @click="openProfitModal()" 
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-purple-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        Distribute Profit
                    </button>
                </div>
            </div>

            <!-- Transaction Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Transaction Summary</h4>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Contributions</span>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">
                            KES {{ number_format($partner->total_contribution, 2) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Withdrawals</span>
                        <span class="text-sm font-medium text-red-600 dark:text-red-400">
                            KES {{ number_format($partner->total_withdrawn, 2) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Invested</span>
                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                            KES {{ number_format($partner->total_invested ?? 0, 2) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Returned</span>
                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                            KES {{ number_format($partner->total_returned ?? 0, 2) }}
                        </span>
                    </div>
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Net Position</span>
                            <span class="text-sm font-semibold {{ ($partner->net_position ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                KES {{ number_format($partner->net_position ?? 0, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="mt-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Transaction History</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">All transactions for this partner</p>
            </div>
            <div class="overflow-x-auto" x-data="partnerTransactionsTable()" x-init="init(@json($data['transactions'] ?? []))">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="relative flex-1 min-w-[150px]">
                            <input type="text" 
                                   x-model="searchTerm" 
                                   @input="filterTable()"
                                   placeholder="Search transactions..." 
                                   class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 pl-10 text-sm text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                            <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <select x-model="filterType" @change="filterTable()"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                <option value="">All Types</option>
                                <option value="contribution">Contributions</option>
                                <option value="withdrawal">Withdrawals</option>
                                <option value="profit_distribution">Profit Distributions</option>
                                <option value="bonus">Bonuses</option>
                                <option value="repayment">Repayments</option>
                            </select>
                        </div>
                    </div>
                </div>

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Balance After</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="transaction in paginatedData" :key="transaction.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300" x-text="transaction.transaction_date || transaction.created_at"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': transaction.type === 'contribution',
                                              'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': transaction.type === 'withdrawal',
                                              'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300': transaction.type === 'profit_distribution',
                                              'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': transaction.type === 'bonus',
                                              'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': transaction.type === 'repayment'
                                          }">
                                        <span x-text="transaction.type ? transaction.type.replace('_', ' ').toUpperCase() : 'N/A'"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-medium" 
                                          :class="transaction.amount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        <span x-text="formatCurrency(transaction.amount)"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300" x-text="formatCurrency(transaction.balance_after)"></td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300" x-text="transaction.reference || 'N/A'"></td>
                            </tr>
                        </template>
                        <tr x-show="filteredData.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No transactions found</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search or filter criteria</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Showing <span x-text="startIndex + 1"></span> to <span x-text="endIndex"></span> of <span x-text="filteredData.length"></span> entries
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="prevPage()" :disabled="currentPage === 1"
                                    class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                Previous
                            </button>
                            <template x-for="page in totalPages" :key="page">
                                <button @click="goToPage(page)"
                                        class="px-3 py-1 rounded text-sm transition-colors"
                                        :class="currentPage === page ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                        x-text="page">
                                </button>
                            </template>
                            <button @click="nextPage()" :disabled="currentPage === totalPages"
                                    class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
@include('partials.modal.partners-create-modal', ['users' => $users ?? []])

<!-- Delete Modal -->
@include('partials.modal.delete')

<!-- Alert Modal -->
@include('partials.modal.alert-modal')

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('partnerTransactionsTable', function() {
        return {
            allData: [],
            filteredData: [],
            paginatedData: [],
            searchTerm: '',
            filterType: '',
            perPage: 10,
            currentPage: 1,

            get startIndex() {
                return (this.currentPage - 1) * this.perPage;
            },
            get endIndex() {
                return Math.min(this.startIndex + this.perPage, this.filteredData.length);
            },
            get totalPages() {
                return Math.ceil(this.filteredData.length / this.perPage);
            },

            init(transactions) {
                this.allData = transactions;
                this.filteredData = [...this.allData];
                this.updateTable();
            },

            updateTable() {
                this.paginatedData = this.filteredData.slice(this.startIndex, this.endIndex);
            },

            filterTable() {
                let data = [...this.allData];

                if (this.searchTerm.trim()) {
                    const term = this.searchTerm.toLowerCase().trim();
                    data = data.filter(item => {
                        return Object.values(item).some(value => 
                            String(value).toLowerCase().includes(term)
                        );
                    });
                }

                if (this.filterType) {
                    data = data.filter(item => item.type === this.filterType);
                }

                this.filteredData = data;
                this.currentPage = 1;
                this.updateTable();
            },

            prevPage() {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.updateTable();
                }
            },

            nextPage() {
                if (this.currentPage < this.totalPages) {
                    this.currentPage++;
                    this.updateTable();
                }
            },

            goToPage(page) {
                this.currentPage = page;
                this.updateTable();
            },

            formatCurrency(value) {
                if (!value && value !== 0) return 'KES 0.00';
                return 'KES ' + parseFloat(value).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        };
    });

    // Delete partner handler
    window.deletePartner = function(id, name) {
        window.dispatchEvent(new CustomEvent('delete-partner', {
            detail: { id, name }
        }));
    };
});
</script>
@endpush

<style>
[x-cloak] {
    display: none !important;
}
</style>
@endsection