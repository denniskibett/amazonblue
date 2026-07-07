{{-- resources/views/reports/partials/table.blade.php --}}
<div x-data="reportTable()" x-init="init()" class="w-full">
    <!-- Table Controls -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500 dark:text-gray-400">Show</label>
            <select x-model="perPage" @change="updateTable()" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="text-sm text-gray-500 dark:text-gray-400">entries</span>
        </div>
        
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <div class="relative flex-1 sm:flex-none">
                <input type="text" 
                       x-model="searchTerm" 
                       @input="filterTable()"
                       placeholder="Search..." 
                       class="w-full sm:w-48 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-1.5 pl-8 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500">
                <div class="absolute left-3 top-1/2 transform -translate-y-1/2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    @foreach($headers as $header)
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-200"
                        @click="sortBy('{{ $header }}')">
                        <div class="flex items-center gap-1">
                            <span>{{ str_replace('_', ' ', $header) }}</span>
                            <template x-if="sortField === '{{ $header }}'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x-bind:d="sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'" />
                                </svg>
                            </template>
                        </div>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <template x-for="(item, index) in paginatedData" :key="index">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        @foreach($headers as $header)
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap" x-text="item['{{ $header }}'] || '-'"></td>
                        @endforeach
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Showing <span x-text="startIndex + 1"></span> to <span x-text="endIndex"></span> of <span x-text="filteredData.length"></span> entries
        </div>
        <div class="flex items-center gap-1">
            <button @click="prevPage()" :disabled="currentPage === 1"
                    class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                Previous
            </button>
            <template x-for="page in totalPages" :key="page">
                <button @click="goToPage(page)"
                        class="px-3 py-1 rounded text-sm"
                        :class="currentPage === page ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                        x-text="page">
                </button>
            </template>
            <button @click="nextPage()" :disabled="currentPage === totalPages"
                    class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                Next
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function reportTable() {
    return {
        // Data
        allData: [],
        filteredData: [],
        paginatedData: [],
        headers: @json($headers ?? []),
        
        // Table state
        searchTerm: '',
        sortField: '',
        sortDirection: 'asc',
        perPage: 10,
        currentPage: 1,
        
        // Computed
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
            this.allData = @json($data ?? []);
            this.filteredData = [...this.allData];
            this.updateTable();
        },
        
        updateTable() {
            const start = this.startIndex;
            const end = this.endIndex;
            this.paginatedData = this.filteredData.slice(start, end);
        },
        
        filterTable() {
            const term = this.searchTerm.toLowerCase().trim();
            if (!term) {
                this.filteredData = [...this.allData];
            } else {
                this.filteredData = this.allData.filter(item => {
                    return Object.values(item).some(value => 
                        String(value).toLowerCase().includes(term)
                    );
                });
            }
            this.currentPage = 1;
            this.updateTable();
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
        }
    }
}
</script>
@endpush