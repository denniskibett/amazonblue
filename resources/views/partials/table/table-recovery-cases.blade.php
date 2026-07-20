@php
    $recoveryCases = $recoveryCases ?? collect();
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6"
     x-data="recoveryTable()"
     x-init="init()">
    
    <!-- Table Header -->
    <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Debt Recovery Cases</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Showing <span id="recoveryShowingStart">1</span> to <span id="recoveryShowingEnd">10</span> of <span id="recoveryTotalCount">{{ $recoveryCases->count() }}</span> cases
            </p>
        </div>
        
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[150px]">
                <input type="text" id="recoverySearch" placeholder="Search cases..." 
                       class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 pl-10">
                <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
            
            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'teller')
            <button onclick="window.dispatchEvent(new CustomEvent('open-case-create'))"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Case
            </button>
            @endif
        </div>
    </div>

    <!-- Status Filter -->
    <div class="flex flex-wrap gap-2 mb-4">
        <button class="recovery-filter-btn px-3 py-1.5 text-xs font-medium rounded-full bg-blue-600 text-white" data-filter="all">All</button>
        <button class="recovery-filter-btn px-3 py-1.5 text-xs font-medium rounded-full bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300" data-filter="open">Open</button>
        <button class="recovery-filter-btn px-3 py-1.5 text-xs font-medium rounded-full bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300" data-filter="in_progress">In Progress</button>
        <button class="recovery-filter-btn px-3 py-1.5 text-xs font-medium rounded-full bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300" data-filter="negotiation">Negotiation</button>
        <button class="recovery-filter-btn px-3 py-1.5 text-xs font-medium rounded-full bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300" data-filter="legal">Legal</button>
        <button class="recovery-filter-btn px-3 py-1.5 text-xs font-medium rounded-full bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300" data-filter="recovered">Recovered</button>
        <button class="recovery-filter-btn px-3 py-1.5 text-xs font-medium rounded-full bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300" data-filter="written_off">Written Off</button>
        <button class="recovery-filter-btn px-3 py-1.5 text-xs font-medium rounded-full bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300" data-filter="closed">Closed</button>
    </div>

    <!-- Table -->
    <div class="w-full overflow-x-auto">
        <table class="min-w-full" id="recoveryTable">
            <thead>
                <tr class="border-gray-100 border-y dark:border-gray-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case #</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Debtor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Overdue</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800" id="recoveryTableBody">
                @forelse($recoveryCases as $case)
                @php
                    $caseData = [
                        'id' => $case->id,
                        'user_id' => $case->user_id,
                        'loan_id' => $case->loan_id,
                        'total_debt_amount' => $case->total_debt_amount,
                        'principal_outstanding' => $case->principal_outstanding,
                        'interest_outstanding' => $case->interest_outstanding,
                        'penalty_outstanding' => $case->penalty_outstanding,
                        'fees_outstanding' => $case->fees_outstanding,
                        'default_date' => $case->default_date ? $case->default_date->format('Y-m-d') : null,
                        'status_id' => $case->status_id,
                        'priority_id' => $case->priority_id,
                        'assigned_to' => $case->assigned_to,
                        'recovery_strategy' => $case->recovery_strategy,
                        'notes' => $case->notes,
                    ];
                @endphp
                <tr class="recovery-row hover:bg-gray-50 dark:hover:bg-gray-800/50 transition duration-150" 
                    data-status="{{ $case->status->slug ?? 'open' }}"
                    data-case-id="{{ $case->id }}"
                    data-case-data='{{ json_encode($caseData) }}'>
                    <td class="px-4 py-3">
                        <a href="{{ route('cases.show', $case) }}" class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                            {{ $case->case_number }}
                        </a>
                        @if($case->loan && $case->loan->is_non_performing)
                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                NPL
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="h-8 w-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs font-medium">
                                {{ $case->user->getInitialsAttribute() ?? 'U' }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $case->user->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $case->user->phone ?? 'No phone' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">KES {{ number_format($case->total_debt_amount, 2) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Recovered: KES {{ number_format($case->getTotalRecovered(), 2) }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @include('partials.recovery.status-badge', ['status' => $case->status])
                    </td>
                    <td class="px-4 py-3">
                        @include('partials.recovery.priority-badge', ['priority' => $case->priority])
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm {{ $case->days_in_default > 90 ? 'text-red-600 font-bold' : ($case->days_in_default > 30 ? 'text-orange-500' : 'text-gray-600') }}">
                            {{ $case->days_in_default }} days
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end space-x-2">
                            <a href="{{ route('cases.show', $case) }}" class="text-blue-600 hover:text-blue-800" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'teller')
                            <button onclick="openEditCase({{ $case->id }})" 
                                    class="text-green-600 hover:text-green-800" 
                                    title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </button>
                            <button onclick="if(confirm('Mark this case as recovered?')) { document.getElementById('recover-form-{{ $case->id }}').submit(); }" 
                                    class="text-green-600 hover:text-green-800" 
                                    title="Mark Recovered">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </button>
                            <form id="recover-form-{{ $case->id }}" method="POST" action="{{ route('cases.recover', $case) }}" style="display:none;">
                                @csrf
                                @method('PATCH')
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No recovery cases found</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search or filter criteria</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function recoveryTable() {
    return {
        currentPage: 1,
        entriesPerPage: 10,
        allRows: [],
        filteredRows: [],
        currentFilter: 'all',
        
        init() {
            this.$nextTick(() => {
                this.allRows = Array.from(document.querySelectorAll('.recovery-row'));
                this.filteredRows = [...this.allRows];
                this.updateTable();
                this.attachEventListeners();
                
                // Listen for refresh events
                window.addEventListener('refresh-cases', () => {
                    location.reload();
                });
            });
        },
        
        attachEventListeners() {
            // Search
            document.getElementById('recoverySearch')?.addEventListener('input', (e) => {
                this.filterUsers(e.target.value);
            });
            
            // Filter buttons
            document.querySelectorAll('.recovery-filter-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const filter = btn.dataset.filter;
                    this.currentFilter = filter;
                    
                    // Update button styles
                    document.querySelectorAll('.recovery-filter-btn').forEach(b => {
                        b.classList.remove('bg-blue-600', 'text-white');
                        b.classList.add('bg-gray-200', 'text-gray-700', 'dark:bg-gray-700', 'dark:text-gray-300');
                    });
                    btn.classList.remove('bg-gray-200', 'text-gray-700', 'dark:bg-gray-700', 'dark:text-gray-300');
                    btn.classList.add('bg-blue-600', 'text-white');
                    
                    this.filterRows();
                });
            });
        },
        
        filterUsers(searchTerm) {
            const term = searchTerm?.toLowerCase() || '';
            
            if (term === '') {
                this.filteredRows = [...this.allRows];
            } else {
                this.filteredRows = this.allRows.filter(row => {
                    const text = row.textContent.toLowerCase();
                    return text.includes(term);
                });
            }
            
            this.filterRows();
        },
        
        filterRows() {
            let rows = [...this.allRows];
            
            // Apply status filter
            if (this.currentFilter !== 'all') {
                rows = rows.filter(row => row.dataset.status === this.currentFilter);
            }
            
            // Apply search filter
            const searchTerm = document.getElementById('recoverySearch')?.value?.toLowerCase() || '';
            if (searchTerm !== '') {
                rows = rows.filter(row => row.textContent.toLowerCase().includes(searchTerm));
            }
            
            this.filteredRows = rows;
            this.updateTable();
        },
        
        updateTable() {
            const startIndex = (this.currentPage - 1) * this.entriesPerPage;
            const endIndex = startIndex + this.entriesPerPage;
            const paginatedRows = this.filteredRows.slice(startIndex, endIndex);
            
            // Hide all rows
            this.allRows.forEach(row => row.style.display = 'none');
            
            // Show paginated rows
            paginatedRows.forEach(row => row.style.display = '');
            
            // Update counters
            const total = this.filteredRows.length;
            const showing = paginatedRows.length;
            
            document.getElementById('recoveryShowingStart').textContent = startIndex + 1;
            document.getElementById('recoveryShowingEnd').textContent = Math.min(endIndex, total);
            document.getElementById('recoveryTotalCount').textContent = total;
        }
    };
}

// Global function to open edit case
function openEditCase(caseId) {
    const row = document.querySelector(`.recovery-row[data-case-id="${caseId}"]`);
    if (row) {
        try {
            // Get the case data from data-case-data attribute
            const caseDataAttr = row.getAttribute('data-case-data');
            if (caseDataAttr) {
                const caseData = JSON.parse(caseDataAttr);
                console.log('Edit case data:', caseData);
                window.dispatchEvent(new CustomEvent('edit-case', {
                    detail: { case: caseData }
                }));
            } else {
                console.error('No case data found for case ID:', caseId);
            }
        } catch (e) {
            console.error('Error parsing case data:', e);
            // Fallback: create minimal case data
            const caseData = {
                id: caseId,
                user_id: parseInt(row.dataset.userId) || null,
                loan_id: parseInt(row.dataset.loanId) || null,
                total_debt_amount: parseFloat(row.dataset.totalDebt) || 0,
                status_id: parseInt(row.dataset.statusId) || null,
                priority_id: parseInt(row.dataset.priorityId) || null,
            };
            window.dispatchEvent(new CustomEvent('edit-case', {
                detail: { case: caseData }
            }));
        }
    } else {
        console.error('Row not found for case ID:', caseId);
    }
}
</script>