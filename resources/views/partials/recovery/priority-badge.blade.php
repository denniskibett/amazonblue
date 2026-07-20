@php
    $priority = $priority ?? null;
    if (!$priority) {
        echo '<span class="text-xs text-gray-500">Unknown</span>';
        return;
    }
    
    $colors = [
        'low' => 'gray',
        'medium' => 'yellow',
        'high' => 'orange',
        'urgent' => 'red',
    ];
    
    $labels = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];
    
    $slug = $priority->slug ?? 'low';
    $color = $colors[$slug] ?? 'gray';
    $label = $labels[$slug] ?? ucfirst($slug);
    
    $bgColor = [
        'gray' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
        'red' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    ][$color] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $bgColor }}">
    @if($slug === 'urgent')
        <svg class="w-3 h-3 mr-1 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
    @endif
    {{ $label }}
</span>