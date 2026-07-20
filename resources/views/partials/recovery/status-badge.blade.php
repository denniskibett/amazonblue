@php
    $status = $status ?? null;
    if (!$status) {
        echo '<span class="text-xs text-gray-500">Unknown</span>';
        return;
    }
    
    $colors = [
        'open' => 'blue',
        'in_progress' => 'yellow', 
        'negotiation' => 'purple',
        'legal' => 'red',
        'recovered' => 'green',
        'written_off' => 'gray',
        'closed' => 'gray',
    ];
    
    $labels = [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'negotiation' => 'Negotiation',
        'legal' => 'Legal',
        'recovered' => 'Recovered',
        'written_off' => 'Written Off',
        'closed' => 'Closed',
    ];
    
    $slug = $status->slug ?? 'open';
    $color = $colors[$slug] ?? 'gray';
    $label = $labels[$slug] ?? ucfirst($slug);
    
    $bgColor = [
        'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        'red' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'green' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'gray' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    ][$color] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $bgColor }}">
    <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-current opacity-75"></span>
    {{ $label }}
</span>