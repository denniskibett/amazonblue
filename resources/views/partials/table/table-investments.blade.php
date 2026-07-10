{{-- resources/views/partials/table/table-investments.blade.php --}}
@php
    $partners = $partners ?? [];
    $users = $users ?? [];
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6"
     x-data="investmentTable()"
     x-init="init()">
  <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
        Investment Portfolio
      </h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        Showing <span id="showingStart">1</span> to <span id="showingEnd">10</span> of <span id="totalCount">{{ count($investments) }}</span> entries
      </p>
    </div>
    
    <div class="flex flex-wrap items-center gap-3">
      <div class="flex items-center">
        <label for="entriesPerPage" class="text-sm text-gray-500 dark:text-gray-400 mr-2 hidden sm:inline">Show:</label>
        <div class="relative">
          <select id="entriesPerPage" class="appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 pr-8">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <div class="absolute right-2 top-1/2 transform -translate-y-1/2 pointer-events-none text-gray-400 dark:text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>
      </div>
      
      <div class="relative flex-1 min-w-[150px]">
        <input type="text" id="investmentSearch" placeholder="Search investments..." class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 pl-10">
        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      <button 
          @click="window.dispatchEvent(new CustomEvent('open-investment-create'))"
          class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-theme-sm font-medium text-gray-500 shadow-theme-xs ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          New Investment
      </button>
    </div>
  </div>

  <div class="w-full overflow-x-auto">
    <table class="min-w-full" id="investmentsTable">
      <thead class="hidden sm:table-header-group">
        <tr class="border-gray-100 border-y dark:border-gray-800">
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortBy('name')">
            <div class="flex items-center justify-between">
              <span>Investment</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sector</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortBy('initial_amount')">
            <div class="flex items-center justify-between">
              <span>Amount</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortBy('return_percentage')">
            <div class="flex items-center justify-between">
              <span>Return</span>
              <span class="sort-icon text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </div>
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
          <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      
      <thead class="sm:hidden">
        <tr class="border-gray-100 border-y dark:border-gray-800">
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Investment</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
          <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100 dark:divide-gray-800" id="investmentsTableBody">
        @forelse($investments as $investment)
        <tr class="investment-row hover:bg-gray-50 transition duration-150" data-investment-id="{{ $investment['id'] }}">
          <td class="py-3 hidden sm:table-cell">
            <div class="flex items-center gap-3">
              <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                <span class="text-blue-600 font-medium">{{ ucfirst(substr($investment['name'], 0, 1)) }}</span>
              </div>
              <div>
                <a href="{{ route('investments.show', $investment['id']) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                  <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $investment['name'] }}</p>
                </a>
                <span class="text-gray-500 text-theme-xs dark:text-gray-400">{{ $investment['company_name'] ?? 'N/A' }}</span>
              </div>
            </div>
          </td>

          <td class="py-3 hidden sm:table-cell">
            <span class="text-gray-800 text-theme-sm dark:text-white/90">{{ $investment['user_name'] ?? 'N/A' }}</span>
          </td>

          <td class="py-3 hidden sm:table-cell">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
              {{ ucfirst(str_replace('_', ' ', $investment['type'])) }}
            </span>
          </td>

          <td class="py-3 hidden sm:table-cell">
            <span class="text-gray-800 text-theme-sm dark:text-white/90">{{ $investment['sector'] ?? 'N/A' }}</span>
          </td>

          <td class="py-3 hidden sm:table-cell">
            <span class="text-gray-800 text-theme-sm dark:text-white/90">{{ $investment['country'] }}</span>
          </td>

          <td class="py-3 hidden sm:table-cell">
            <div>
              <p class="text-gray-800 text-theme-sm dark:text-white/90">KES {{ number_format($investment['initial_amount'], 2) }}</p>
              <span class="text-gray-500 text-theme-xs dark:text-gray-400">Funded: KES {{ number_format($investment['total_funding_raised'] ?? 0, 2) }}</span>
            </div>
          </td>

          <td class="py-3 hidden sm:table-cell">
            <span class="font-medium text-theme-sm" 
                  :class="{{ $investment['return_percentage'] ?? 0 }} >= 15 ? 'text-green-600 dark:text-green-400' : ({{ $investment['return_percentage'] ?? 0 }} >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400')">
              {{ number_format($investment['return_percentage'] ?? 0, 1) }}%
            </span>
          </td>

          <td class="py-3">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium investment-status"
                  :class="{
                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': '{{ $investment['status'] }}' === 'pipeline' || '{{ $investment['status'] }}' === 'due_diligence',
                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': '{{ $investment['status'] }}' === 'active',
                    'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': '{{ $investment['status'] }}' === 'matured',
                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': '{{ $investment['status'] }}' === 'liquidated',
                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': '{{ $investment['status'] }}' === 'write_off'
                  }">
              {{ ucfirst(str_replace('_', ' ', $investment['status'])) }}
            </span>
          </td>

          <td class="py-3 text-right">
            <div class="flex justify-end space-x-3">
              <a href="{{ route('investments.show', $investment['id']) }}" class="text-blue-600 hover:text-blue-900" title="View">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
              </a>

              <button @click="editInvestment({{ $investment['id'] }})" class="text-green-600 hover:text-green-900" title="Edit">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                </svg>
              </button>

              <button @click="deleteInvestment({{ $investment['id'] }}, '{{ addslashes($investment['name']) }}')" class="text-red-600 hover:text-red-900" title="Delete">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
              </button>
            </div>
          </td>

          <!-- Mobile View -->
          <td class="py-3 sm:hidden">
            <div class="flex items-center gap-3">
              <div class="h-[40px] w-[40px] overflow-hidden rounded-md bg-blue-100 flex items-center justify-center">
                <a href="{{ route('investments.show', $investment['id']) }}">
                  <span class="text-blue-600 font-medium">{{ ucfirst(substr($investment['name'], 0, 1)) }}</span>
                </a>
              </div>
              <div>
                <a href="{{ route('investments.show', $investment['id']) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                  <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $investment['name'] }}</p>
                </a>
                <span class="text-gray-500 text-theme-xs dark:text-gray-400">{{ $investment['company_name'] ?? 'N/A' }}</span>
              </div>
            </div>
          </td>

          <td class="py-3 sm:hidden">
            <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">KES {{ number_format($investment['initial_amount'], 2) }}</p>
          </td>

          <td class="py-3 sm:hidden text-right">
            <a href="{{ route('investments.show', $investment['id']) }}" class="text-blue-600 hover:text-blue-900 inline-block mr-2" title="View">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
              </svg>
            </a>
          
            <button @click="editInvestment({{ $investment['id'] }})" class="text-green-600 hover:text-green-900 inline-block mr-2" title="Edit">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
              </svg>
            </button>
          
            <button @click="deleteInvestment({{ $investment['id'] }}, '{{ addslashes($investment['name']) }}')" class="text-red-600 hover:text-red-900 inline-block mr-2" title="Delete">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
              </svg>
            </button>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="9" class="py-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No investments found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search or filter criteria</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

    <div class="flex flex-col items-center justify-between px-2 py-4 sm:flex-row sm:px-0">
      <div class="hidden sm:flex">
        <p class="text-sm text-gray-700 dark:text-gray-400">
          Showing <span id="paginationStart">1</span> to <span id="paginationEnd">10</span> of <span id="paginationTotal">{{ count($investments) }}</span> results
        </p>
      </div>
      <div class="flex-1 flex justify-between sm:justify-end">
        <button id="prevPage" class="relative inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
          Previous
        </button>
        <div id="paginationNumbers" class="hidden sm:flex"></div>
        <button id="nextPage" class="relative ml-3 inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
          Next
        </button>
      </div>
    </div>
  </div>

  <!-- Create/Edit Modal -->
  @include('partials.modal.investments-create-modal', ['partners' => $partners, 'users' => $users])

  <!-- Show Modal -->
  @include('partials.modal.investments-show-modal')

  <!-- Alert Modal -->
  @include('partials.modal.alert-modal')

  <!-- Delete Modal -->
  @include('partials.modal.delete')
</div>

<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('investmentTable', function() {
        return {
            allData: [],
            filteredData: [],
            paginatedData: [],
            searchTerm: '',
            sortField: '',
            sortDirection: 'asc',
            perPage: 10,
            currentPage: 1,
            filters: {
                status: '',
                type: ''
            },

            get startIndex() {
                return (this.currentPage - 1) * this.perPage;
            },
            get endIndex() {
                return Math.min(this.startIndex + this.perPage, this.filteredData.length);
            },
            get totalPages() {
                return Math.ceil(this.filteredData.length / this.perPage);
            },

            init() {
                console.log('Investment table initialized');
                this.allData = @json($investments);
                this.filteredData = [...this.allData];
                this.updateTable();

                window.addEventListener('refresh-investments', () => {
                    console.log('Refresh event received');
                    this.refreshData();
                });
            },

            openCreateModal() {
                console.log('Open create modal triggered');
                window.dispatchEvent(new CustomEvent('open-investment-create'));
            },

            showInvestment(investment) {
                console.log('Show investment:', investment);
                window.dispatchEvent(new CustomEvent('show-investment', {
                    detail: { investment }
                }));
            },

            editInvestment(id) {
                const investment = this.allData.find(item => item.id === id);
                if (investment) {
                    window.dispatchEvent(new CustomEvent('edit-investment', {
                        detail: { investment }
                    }));
                }
            },

            deleteInvestment(id, name) {
                window.dispatchEvent(new CustomEvent('delete-investment', {
                    detail: { id, name }
                }));
            },

            filterTable() {
                let data = [...this.allData];

                if (this.searchTerm.trim()) {
                    const term = this.searchTerm.toLowerCase().trim();
                    data = data.filter(item =>
                        Object.values(item).some(value =>
                            String(value).toLowerCase().includes(term)
                        )
                    );
                }

                if (this.filters.status) {
                    data = data.filter(item => item.status === this.filters.status);
                }
                if (this.filters.type) {
                    data = data.filter(item => item.type === this.filters.type);
                }

                this.filteredData = data;
                this.currentPage = 1;
                this.updateTable();
            },

            applyFilters() {
                this.filterTable();
            },

            sortBy(field) {
                if (this.sortField === field) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortField = field;
                    this.sortDirection = 'asc';
                }

                this.filteredData.sort((a, b) => {
                    const valA = a[field] ?? '';
                    const valB = b[field] ?? '';
                    const isNumeric = !isNaN(valA) && !isNaN(valB);

                    if (isNumeric) {
                        return this.sortDirection === 'asc' ? valA - valB : valB - valA;
                    }
                    return this.sortDirection === 'asc'
                        ? String(valA).localeCompare(String(valB))
                        : String(valB).localeCompare(String(valA));
                });

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

            updateTable() {
                this.paginatedData = this.filteredData.slice(this.startIndex, this.endIndex);
            },

            formatCurrency(value) {
                if (!value && value !== 0) return 'KES 0.00';
                return 'KES ' + parseFloat(value).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            refreshData() {
                fetch('{{ route("investments.data") }}')
                    .then(response => response.json())
                    .then(data => {
                        this.allData = data.data;
                        this.filterTable();
                    })
                    .catch(error => {
                        console.error('Error refreshing data:', error);
                    });
            }
        };
    });
});
</script>