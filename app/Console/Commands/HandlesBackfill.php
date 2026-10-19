<?php

namespace App\Console\Commands;

use App\Support\Handle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give every user and partner without one a wallet handle (rule 9), oldest record first, so on a
 * collision the earlier account keeps the plain handle and later ones get 2, 3, ...
 * Existing handles are never changed. Soft-deleted users get one too, so their handle isn't reused.
 */
class HandlesBackfill extends Command
{
    protected $signature = 'handles:backfill {--dry-run : List the handles without saving them}';

    protected $description = 'Generate @handles for users and partners that have none (lowercase name, collisions numbered).';

    public function handle(): int
    {
        foreach (['users', 'partners'] as $table) {
            if (! Schema::hasColumn($table, 'handle')) {
                $this->error("{$table}.handle is missing: run the migration (or database/sql/2026_10_18_wallet_handles.sql) first.");

                return self::FAILURE;
            }
        }

        $dry = (bool) $this->option('dry-run');
        $taken = DB::table('users')->whereNotNull('handle')->pluck('handle')
            ->merge(DB::table('partners')->whereNotNull('handle')->pluck('handle'))
            ->map(fn ($h) => strtolower($h))->flip()->all();

        $plan = [];
        $users = DB::table('users')->whereNull('handle')->orderBy('id')->get(['id', 'name']);
        foreach ($users as $u) {
            $plan[] = ['users', $u->id, $u->name, Handle::fromPersonName($u->name) ?: 'user'];
        }
        $partners = DB::table('partners')->whereNull('handle')->orderBy('id')->get(['id', 'user_id', 'name', 'company_name', 'type']);
        foreach ($partners as $p) {
            if ($p->user_id) {
                // a user's own partner record (e.g. a broker) shares the user's handle
                $plan[] = ['partners', $p->id, $p->name ?: $p->company_name, null, $p->user_id];
                continue;
            }
            $name = $p->name ?: $p->company_name;
            $base = in_array($p->type, Handle::COMPANY_TYPES, true) ? Handle::compact($name) : Handle::fromPersonName($name);
            $plan[] = ['partners', $p->id, $name, $base ?: 'partner'];
        }

        $rows = [];
        $collisions = 0;
        $userHandles = DB::table('users')->whereNotNull('handle')->pluck('handle', 'id')->all();
        $partnerHandles = DB::table('partners')->whereNotNull('handle')->pluck('handle')->flip()->all();
        foreach ($plan as $item) {
            [$table, $id, $name, $base] = $item;
            $linkedUser = $item[4] ?? null;
            if ($linkedUser && isset($userHandles[$linkedUser]) && ! isset($partnerHandles[$userHandles[$linkedUser]])) {
                $partnerHandles[$userHandles[$linkedUser]] = true;
                $rows[] = [$table, $id, $name, '@' . $userHandles[$linkedUser], 'same as user #' . $linkedUser];
                continue;
            }
            if ($linkedUser) {
                $base = Handle::compact($name) ?: 'partner';
            }
            $base = substr($base, 0, 60);
            $handle = $base;
            for ($n = 2; isset($taken[$handle]); $n++) {
                $handle = $base . $n;
            }
            $taken[$handle] = true;
            if ($table === 'users') {
                $userHandles[$id] = $handle;
            } else {
                $partnerHandles[$handle] = true;
            }
            if ($handle !== $base) {
                $collisions++;
            }
            $rows[] = [$table, $id, $name, '@' . $handle, $handle !== $base ? 'collision with @' . $base : ''];
        }

        if (! $rows) {
            $this->info('Every user and partner already has a handle.');

            return self::SUCCESS;
        }

        $this->table(['table', 'id', 'name', 'handle', 'note'], $rows);

        if ($dry) {
            $this->info(sprintf('Dry run: %d handle(s) would be set, %d collision(s) numbered. Nothing saved.', count($rows), $collisions));

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as [$table, $id, , $handle]) {
                DB::table($table)->where('id', $id)->whereNull('handle')->update(['handle' => ltrim($handle, '@')]);
            }
        });
        $this->info(sprintf('%d handle(s) set, %d collision(s) numbered.', count($rows), $collisions));

        return self::SUCCESS;
    }
}
