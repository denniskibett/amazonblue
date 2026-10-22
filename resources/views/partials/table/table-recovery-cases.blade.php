@php
    $recoveryCases = $recoveryCases ?? collect();
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6"
     x-data="recoveryTable()"
     x-init="init()">
    
    <!-- Table Header: search, status filter, paging and sort come from the standard table engine -->
    <div class="mb-4"><h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Debt Recovery Cases</h3></div>
    <div id="recovery-tools" class="contents">
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('teller'))
            <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-case-create'))"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Case
            </button>
            @endif
    </div>

    <!-- Table -->
    <div class="w-full overflow-x-auto">
        <table class="min-w-full" id="recoveryTable" data-table data-table-search="Search cases" data-table-buttons="#recovery-tools > *">
            <thead>
                <tr class="border-gray-100 border-y dark:border-gray-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case #</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Debtor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" data-filter="status">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" data-filter="priority">Priority</th>
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
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90 cursor-pointer hover:text-brand-500" data-user-peek="{{ $case->user_id }}">{{ $case->user->name }}</p>
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
                        @php
                            $statusColors = [
                                'open' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                'in_progress' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                'negotiation' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                                'legal' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                'recovered' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                'written_off' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                'closed' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            ];
                            $statusSlug = $case->status->slug ?? 'open';
                            $statusClass = $statusColors[$statusSlug] ?? $statusColors['open'];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                            {{ $case->status->name ?? 'Open' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $priorityColors = [
                                'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                'low' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                            ];
                            $prioritySlug = $case->priority->slug ?? 'medium';
                            $priorityClass = $priorityColors[$prioritySlug] ?? $priorityColors['medium'];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $priorityClass }}">
                            {{ $case->priority->name ?? 'Medium' }}
                        </span>
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
                            @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('teller'))
                            <button onclick="openEditCase({{ $case->id }})" 
                                    class="text-green-600 hover:text-green-800" 
                                    title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </button>
                            <button type="button" data-confirm="Mark this case as recovered?" data-confirm-submit="recover-form-{{ $case->id }}" 
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
                <tr data-table-skip>
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