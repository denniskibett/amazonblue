<div class="space-y-4" x-data="actionTimeline(@json($actions ?? []))">
    <template x-for="action in actions" :key="action.id">
        <div class="flex gap-3 border-b border-gray-100 dark:border-gray-700 pb-4 last:border-0 last:pb-0">
            <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-full flex items-center justify-center"
                     :class="{
                       'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400': action.action_type === 'call' || action.action_type === 'phone_call',
                       'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400': action.action_type === 'email',
                       'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400': action.action_type === 'meeting',
                       'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400': action.action_type === 'legal' || action.action_type === 'legal_notice',
                       'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400': action.action_type === 'payment' || action.action_type === 'payment_arrangement',
                       'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400': action.action_type === 'negotiation',
                       'bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400': action.action_type === 'field_visit' || action.action_type === 'visit',
                       'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400': !['call','phone_call','email','meeting','legal','legal_notice','payment','payment_arrangement','negotiation','field_visit','visit'].includes(action.action_type)
                     }">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              :d="getActionIcon(action.action_type)"/>
                    </svg>
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="action.action_type_label || action.action_type || 'Action'"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="action.created_at_diff || action.created_at || 'Just now'"></p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                          :class="{
                            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': action.outcome === 'successful',
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': action.outcome === 'pending' || action.outcome === 'promise_to_pay',
                            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': action.outcome === 'failed' || action.outcome === 'refused',
                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': !['successful','pending','promise_to_pay','failed','refused'].includes(action.outcome)
                          }"
                          x-text="action.outcome ? action.outcome.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A'">
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" x-text="action.description || action.notes || 'No description'"></p>
                <div class="mt-2 flex flex-wrap gap-2" x-show="action.amount_collected > 0">
                    <span class="inline-flex items-center text-xs text-green-600 dark:text-green-400">
                        <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="'Collected: KES ' + parseFloat(action.amount_collected).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')"></span>
                    </span>
                </div>
                <div class="mt-2 flex flex-wrap gap-2" x-show="action.next_action_date">
                    <span class="inline-flex items-center text-xs text-blue-600 dark:text-blue-400">
                        <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span x-text="'Next: ' + action.next_action_date"></span>
                    </span>
                </div>
            </div>
        </div>
    </template>
    <div x-show="actions.length === 0" class="text-center py-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">No actions recorded yet</p>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('actionTimeline', (initialActions) => ({
        actions: initialActions || [],
        
        getActionIcon(type) {
            const icons = {
                'call': 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
                'phone_call': 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
                'email': 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                'meeting': 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                'visit': 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                'field_visit': 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                'legal': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z',
                'legal_notice': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z',
                'payment': 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'payment_arrangement': 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'negotiation': 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z',
                'letter': 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                'sms': 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z'
            };
            return icons[type] || icons['call'];
        }
    }));
});
</script>
@endpush