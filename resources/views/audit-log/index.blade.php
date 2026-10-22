@extends('layouts.app')

@section('content')
@php
    // one line per changed field: old → new (owner, 22 Oct 2026: Loans Due look)
    $show = fn ($v) => \Illuminate\Support\Str::limit(is_array($v) ? json_encode($v) : (is_bool($v) ? ($v ? 'true' : 'false') : (string) ($v ?? '—')), 60);
    $diff = function ($old, $new) use ($show) {
        $old = is_array($old) ? $old : [];
        $new = is_array($new) ? $new : [];
        return collect(array_unique(array_merge(array_keys($new), array_keys($old))))
            ->reject(fn ($k) => in_array($k, ['updated_at', 'created_at', 'event'], true))
            ->map(fn ($k) => ['key' => str_replace('_', ' ', $k), 'old' => array_key_exists($k, $old) ? $show($old[$k]) : null, 'new' => array_key_exists($k, $new) ? $show($new[$k]) : null]);
    };
    $tone = ['create' => 'green', 'update' => 'blue', 'delete' => 'red', 'force_delete' => 'red', 'restore' => 'amber'];
    $field = 'h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-700 focus:border-brand-300 focus:outline-none focus:ring focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300';
@endphp
<div>
    <x-ui.page-header title="Audit Log" sub="Every change to loans, payments, people and settings: who, when, and the values before and after." />
    <x-ui.page-metrics page="audit-log" />

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-4 px-4 pt-4 sm:px-6 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Changes</h3>
                <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Showing {{ number_format($logs->firstItem() ?? 0) }} to {{ number_format($logs->lastItem() ?? 0) }} of {{ number_format($logs->total()) }} entries</p>
            </div>
            <form method="GET" action="{{ route('audit-log.index') }}" class="flex flex-wrap items-end gap-2">
                <label class="sr-only" for="al-table">Record type</label>
                <select id="al-table" name="table" class="{{ $field }}">
                    <option value="">All records</option>
                    @foreach($tables as $table)
                        <option value="{{ $table }}" @selected(request('table') === $table)>{{ str_replace('_', ' ', $table) }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="al-record">Record #</label>
                <input id="al-record" type="number" name="record" value="{{ request('record') }}" placeholder="Record #" class="{{ $field }} w-28">
                <label class="sr-only" for="al-user">User</label>
                <select id="al-user" name="user" class="{{ $field }}">
                    <option value="">Anyone</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected((string) request('user') === (string) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="al-action">Action</label>
                <select id="al-action" name="action" class="{{ $field }}">
                    <option value="">Any action</option>
                    @foreach(['create', 'update', 'delete', 'restore', 'force_delete'] as $a)
                        <option value="{{ $a }}" @selected(request('action') === $a)>{{ ucfirst(str_replace('_', ' ', $a)) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="h-10 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white hover:bg-brand-600">Filter</button>
                @if(request()->hasAny(['table', 'record', 'user', 'action']))
                    <a href="{{ route('audit-log.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-4 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">Clear</a>
                @endif
            </form>
        </div>

        <div class="max-w-full overflow-x-auto px-4 pb-3 pt-4 sm:px-6">
            <table class="w-full min-w-[760px] text-left text-theme-sm text-gray-700 dark:text-gray-300">
                <caption class="sr-only">Audit log</caption>
                <thead class="border-y border-gray-100 dark:border-gray-800">
                    <tr>
                        @foreach (['Who', 'Action', 'Record', 'What changed', 'When'] as $h)
                            <th scope="col" class="whitespace-nowrap py-3 pr-4 text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($logs as $log)
                        @php $changes = $diff($log->old_values, $log->new_values); @endphp
                        <tr class="align-top">
                            <td class="whitespace-nowrap py-3 pr-4">
                                @if ($log->user)
                                    <x-staff.person :user="$log->user" :sub="'User #' . $log->user->id" />
                                @else
                                    <x-staff.person name="System" sub="scheduled or automatic" />
                                @endif
                            </td>
                            <td class="whitespace-nowrap py-3 pr-4">
                                <x-staff.pill :tone="$tone[$log->action] ?? 'gray'" dot>{{ $log->formatted_action }}</x-staff.pill>
                                @if(!empty($log->new_values['event']))
                                    <span class="mt-1 block text-theme-xs text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', $log->new_values['event']) }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap py-3 pr-4">
                                <a class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90" href="{{ route('audit-log.index', ['table' => $log->table_name, 'record' => $log->record_id]) }}">{{ ucfirst(str_replace('_', ' ', $log->table_name)) }} #{{ $log->record_id }}</a>
                                <span class="block text-theme-xs text-gray-500 dark:text-gray-400">all changes to this record</span>
                            </td>
                            <td class="py-3 pr-4">
                                @if ($changes->isEmpty())
                                    <span class="text-theme-xs text-gray-400">—</span>
                                @else
                                    <div class="max-w-xl space-y-0.5 text-theme-xs" x-data="{ all: false }">
                                        @foreach ($changes as $i => $c)
                                            <div class="flex flex-wrap gap-x-1.5" @if ($i >= 4) x-show="all" x-cloak @endif>
                                                <span class="text-gray-500 dark:text-gray-400">{{ $c['key'] }}</span>
                                                @if ($c['old'] !== null)<span class="text-error-600 line-through decoration-error-300 dark:text-error-400">{{ $c['old'] }}</span><span class="text-gray-400">→</span>@endif
                                                <span class="text-gray-800 dark:text-white/90">{{ $c['new'] ?? 'removed' }}</span>
                                            </div>
                                        @endforeach
                                        @if ($changes->count() > 4)
                                            <button type="button" class="font-medium text-brand-500 hover:underline" @click="all = !all" x-text="all ? 'Show less' : 'Show {{ $changes->count() - 4 }} more'"></button>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap py-3 pr-4">
                                <span>{{ optional($log->created_at)->format('j M Y, H:i') }}</span>
                                <span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ optional($log->created_at)->diffForHumans(short: true) }}{{ $log->ip_address ? ' · ' . $log->ip_address : '' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-staff.empty title="No audit entries yet" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="border-t border-gray-100 px-4 py-4 dark:border-gray-800 sm:px-6">{{ $logs->onEachSide(1)->links() }}</div>
        @endif
    </div>
</div>
@endsection
