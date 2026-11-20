<?php

namespace App\Support;

/** Tailwind class strings shared by the funding CRM and finance review pages (staff/funding, staff/finance). */
class CrmUi
{
    public const CLASSES = [
        'card' => 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6',
        'input' => 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90',
        'area' => 'w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90',
        'btn' => 'inline-flex h-11 items-center rounded-lg bg-brand-500 px-4 text-sm font-medium text-white hover:bg-brand-600',
        'ghost' => 'inline-flex h-11 items-center rounded-lg border border-gray-300 px-4 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300',
        'th' => 'px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400',
        'td' => 'px-4 py-3 text-sm text-gray-700 dark:text-gray-300',
        'label' => 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400',
    ];
}
