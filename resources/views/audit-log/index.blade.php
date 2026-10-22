@extends('layouts.app')

@section('content')
@php
    $fmt = function ($values) {
        if (!is_array($values) || !$values) return '';
        return collect($values)->map(function ($v, $k) {
            $v = is_array($v) ? json_encode($v) : (is_bool($v) ? ($v ? 'true' : 'false') : ($v ?? 'null'));
            return $k . ': ' . \Illuminate\Support\Str::limit((string) $v, 80);
        })->implode("\n");
    };
    $badge = [
        'create' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'update' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'delete' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'force_delete' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'restore' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    ];
@endphp
<div>
    <x-ui.page-header title="Audit Log" sub="Every change to loans, payments, people and settings: who, when, and the values before and after." />
    <x-ui.page-metrics page="audit-log" />

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
        <form method="GET" action="{{ route('audit-log.index') }}" class="flex flex-wrap items-end gap-3 mb-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Record type</label>
                <select name="table" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <option value="">All</option>
                    @foreach($tables as $table)
                        <option value="{{ $table }}" @selected(request('table') === $table)>{{ str_replace('_', ' ', $table) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Record #</label>
                <input type="number" name="record" value="{{ request('record') }}" class="w-28 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">User</label>
                <select name="user" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <option value="">All</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected((string) request('user') === (string) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Action</label>
                <select name="action" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <option value="">All</option>
                    @foreach(['create', 'update', 'delete', 'restore', 'force_delete'] as $a)
                        <option value="{{ $a }}" @selected(request('action') === $a)>{{ ucfirst(str_replace('_', ' ', $a)) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Filter</button>
            @if(request()->hasAny(['table', 'record', 'user', 'action']))
                <a href="{{ route('audit-log.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">Clear</a>
            @endif
        </form>

        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ number_format($logs->total()) }} entries</p>

        <div class="w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-gray-100 border-y dark:border-gray-800">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">When</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Who</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Record</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Before</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">After</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($logs as $log)
                        <tr class="align-top">
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ optional($log->created_at)->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $log->user->name ?? 'System' }}</td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $badge[$log->action] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">{{ $log->formatted_action }}</span>
                                @if(!empty($log->new_values['event']))
                                    <span class="block mt-1 text-xs text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', $log->new_values['event']) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                <a class="hover:text-blue-600" href="{{ route('audit-log.index', ['table' => $log->table_name, 'record' => $log->record_id]) }}">{{ str_replace('_', ' ', $log->table_name) }} #{{ $log->record_id }}</a>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400"><pre class="whitespace-pre-wrap font-mono max-w-xs">{{ $fmt($log->old_values) }}</pre></td>
                            <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300"><pre class="whitespace-pre-wrap font-mono max-w-xs">{{ $fmt($log->new_values) }}</pre></td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr data-table-skip><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No audit entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
