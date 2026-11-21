<?php

namespace App\Console\Commands;

use App\Support\Phone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Data clean-up from the frontend audit (20 Nov 2026). Dry run by default; --apply writes.
 *
 * Changes only what is certain: a Kenyan mobile typed as 07…, 7…, 254… or "+254 7…" becomes +2547… (the app's one
 * stored format, App\Support\Phone). A users.phone that would collide with another user's number is not changed.
 * Everything else (too short, unknown country, collisions) is LISTED for a person and never changed.
 * Before --apply it saves every old value to storage/app/private/repairs/phones-<time>.json, so it can be undone.
 */
class DataRepairPhones extends Command
{
    protected $signature = 'data:repair-phones {--apply : Write the changes (default: dry run)}';

    protected $description = 'Store every Kenyan phone as +2547… where that is certain; list the rest for a person';

    /** table => phone columns */
    public const COLUMNS = [
        'users' => ['phone', 'kin_phone'],
        'contacts' => ['phone', 'phone_2'],
        'partners' => ['phone'],
        'businesses' => ['phone'],
        'business_members' => ['phone'],
        'employments' => ['employer_phone', 'hr_contact_phone', 'supervisor_phone'],
        'enquiries' => ['phone'],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $changes = [];
        $listed = [];
        foreach (self::COLUMNS as $table => $cols) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($cols as $col) {
                if (! Schema::hasColumn($table, $col)) {
                    continue;
                }
                DB::table($table)->whereNotNull($col)->where($col, '<>', '')->where($col, 'not like', '+254%')->orderBy('id')
                    ->select('id', $col)->each(function ($row) use ($table, $col, &$changes, &$listed) {
                        $old = (string) $row->{$col};
                        $new = Phone::normal($old);
                        if ($new !== null && preg_match('/^\+254[17]\d{8}$/', $new)) {
                            if ($table === 'users' && $col === 'phone' && DB::table('users')->where('phone', $new)->where('id', '<>', $row->id)->exists()) {
                                $listed[] = [$table, $col, $row->id, $this->mask($old), 'another user already has ' . $this->mask($new)];

                                return;
                            }
                            $changes[] = [$table, $col, $row->id, $old, $new];
                        } elseif (str_starts_with($old, '+') && $new && preg_match('/^\+\d{10,15}$/', $new)) {
                            // a foreign number typed with its country code: right as it is
                        } else {
                            $listed[] = [$table, $col, $row->id, $this->mask($old), 'not a phone number we can read: ask the person'];
                        }
                    });
            }
        }

        $by = collect($changes)->groupBy(fn ($c) => $c[0] . '.' . $c[1])->map->count();
        $this->info(($apply ? 'Changing' : 'Would change') . ' ' . count($changes) . ' phone number(s) to +2547…:');
        foreach ($by as $k => $n) {
            $this->line("  $k: $n");
        }
        $this->info(count($listed) . ' left for a person (not changed):');
        foreach ($listed as [$t, $c, $id, $v, $why]) {
            $this->line("  $t.$c #$id $v: $why");
        }
        if (! $apply || ! $changes) {
            $this->line($apply ? 'Nothing to change.' : 'Dry run. Run with --apply to write (an undo file is saved first).');

            return self::SUCCESS;
        }

        $undo = 'repairs/phones-' . now()->format('Ymd-His') . '.json';
        Storage::disk('local')->put($undo, json_encode(array_map(fn ($c) => ['table' => $c[0], 'column' => $c[1], 'id' => $c[2], 'old' => $c[3], 'new' => $c[4]], $changes), JSON_PRETTY_PRINT));
        DB::transaction(function () use ($changes) {
            foreach ($changes as [$t, $c, $id, $old, $new]) {
                DB::table($t)->where('id', $id)->where($c, $old)->update([$c => $new]);
            }
        });
        $this->info('Done. Undo file: storage/app/private/' . $undo);

        return self::SUCCESS;
    }

    private function mask(string $v): string
    {
        return strlen($v) > 6 ? substr($v, 0, 4) . '…' . substr($v, -3) : '…' . substr($v, -2);
    }
}
