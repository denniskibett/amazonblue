<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\User;
use App\Support\PrivateFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Moves personal documents off the public disk (storage/app/public, reachable at /storage/...) to the
 * private disk (storage/app/private), where they are served only through authorised routes:
 *   - signed loan agreements (agreements/loan_agreement_{id}.pdf); consented loans get the copy frozen
 *     on the loan (agreement_path, sha256, signed_at = consent date, version "legacy")
 *   - KYC files: ID images, passport photos, signatures (users.id_front_path, id_back_path, avatar, signature)
 *
 * KYC files keep the same relative path, so users.* columns stay valid (the app reads the private disk
 * first, then the public one). Every file is copied, checked byte-for-byte, then removed from public.
 * Nothing is deleted without a verified private copy. Safe to run again: moved files are skipped.
 */
class FilesMakePrivate extends Command
{
    protected $signature = 'files:make-private {--dry-run : Show what would move without changing anything}';

    protected $description = 'Move signed agreements and KYC documents from the public disk to the private disk.';

    // public-disk folders that hold KYC documents (old and new upload folders)
    private const KYC_FOLDERS = ['id_cards', 'id-documents', 'avatars', 'signatures', 'images/signatures'];

    private bool $dry = false;

    private array $stats = ['moved' => 0, 'already_private' => 0, 'conflicts' => 0, 'frozen' => 0];

    public function handle(): int
    {
        $this->dry = (bool) $this->option('dry-run');
        if ($this->dry) {
            $this->warn('Dry run: nothing will be moved or written.');
        }

        $this->moveAgreements();
        $this->moveKycFiles();
        $this->reportMissing();

        $this->newLine();
        $this->table(['moved', 'already private', 'conflicts (left in place)', 'agreements frozen'], [array_values($this->stats)]);

        return self::SUCCESS;
    }

    private function moveAgreements(): void
    {
        $this->info('Loan agreements');
        foreach (Storage::disk('public')->files('agreements') as $file) {
            if (! preg_match('#^agreements/loan_agreement_(\d+)\.pdf$#', $file, $m)) {
                $this->moveFile($file, 'agreements/legacy/' . basename($file));
                continue;
            }

            $loan = Loan::withTrashed()->find((int) $m[1]);
            $consented = $loan && $loan->consent;
            // consented copies become the loan's frozen agreement; drafts and orphans are kept aside
            $target = $consented ? $file : 'agreements/legacy/' . basename($file);
            if (! $this->moveFile($file, $target)) {
                continue;
            }

            if ($consented && ! $loan->agreement_sha256) {
                $this->line("  freeze loan #{$loan->id} -> {$target}");
                $this->stats['frozen']++;
                if (! $this->dry) {
                    $contents = Storage::disk(PrivateFiles::DISK)->get($target);
                    DB::table('loans')->where('id', $loan->id)->whereNull('agreement_sha256')->update([
                        'agreement_path' => $target,
                        'agreement_sha256' => hash('sha256', $contents),
                        'agreement_signed_at' => $loan->consent_date ?? $loan->borrow_date,
                        'agreement_version' => 'legacy',
                    ]);
                }
            }
        }

        // a re-run after a partial run: copies already private but not yet frozen
        foreach (Storage::disk(PrivateFiles::DISK)->files('agreements') as $file) {
            if (preg_match('#^agreements/loan_agreement_(\d+)\.pdf$#', $file, $m)
                && ($loan = Loan::withTrashed()->find((int) $m[1])) && $loan->consent && ! $loan->agreement_sha256) {
                $this->line("  freeze loan #{$loan->id} -> {$file}");
                $this->stats['frozen']++;
                if (! $this->dry) {
                    DB::table('loans')->where('id', $loan->id)->whereNull('agreement_sha256')->update([
                        'agreement_path' => $file,
                        'agreement_sha256' => hash('sha256', Storage::disk(PrivateFiles::DISK)->get($file)),
                        'agreement_signed_at' => $loan->consent_date ?? $loan->borrow_date,
                        'agreement_version' => 'legacy',
                    ]);
                }
            }
        }
    }

    private function moveKycFiles(): void
    {
        $this->info('KYC documents');
        foreach (self::KYC_FOLDERS as $folder) {
            foreach (Storage::disk('public')->allFiles($folder) as $file) {
                $this->moveFile($file, $file);
            }
        }

        // anything a user record points to that sits outside those folders
        $columns = array_values(PrivateFiles::USER_DOCUMENTS);
        User::withTrashed()->where(fn ($q) => collect($columns)->each(fn ($c) => $q->orWhereNotNull($c)))
            ->select(array_merge(['id'], $columns))->orderBy('id')
            ->each(function (User $user) use ($columns) {
                foreach ($columns as $column) {
                    $path = $user->{$column};
                    if ($path && ! filter_var($path, FILTER_VALIDATE_URL) && ! str_contains($path, '..') && Storage::disk('public')->exists($path)) {
                        $this->moveFile($path, $path);
                    }
                }
            });
    }

    /**
     * Copy public $from to private $to, verify, then remove the public copy. False if left in place.
     */
    private function moveFile(string $from, string $to): bool
    {
        $public = Storage::disk('public');
        $private = Storage::disk(PrivateFiles::DISK);
        $contents = $public->get($from);

        if ($private->exists($to)) {
            if (hash('sha256', $private->get($to)) !== hash('sha256', $contents)) {
                $this->error("  conflict: {$to} already private with different content; public copy left in place");
                $this->stats['conflicts']++;

                return false;
            }
            $this->line("  already private: {$to} (removing public copy)");
            $this->stats['already_private']++;
            if (! $this->dry) {
                $public->delete($from);
            }

            return true;
        }

        $this->line("  move {$from}" . ($from !== $to ? " -> {$to}" : ''));
        $this->stats['moved']++;
        if ($this->dry) {
            return true;
        }

        $private->put($to, $contents);
        if (hash('sha256', (string) $private->get($to)) !== hash('sha256', $contents)) {
            $this->error("  copy of {$from} did not verify; public copy left in place");
            $this->stats['conflicts']++;

            return false;
        }
        $public->delete($from);

        return true;
    }

    private function reportMissing(): void
    {
        $missing = 0;
        foreach (array_values(PrivateFiles::USER_DOCUMENTS) as $column) {
            User::withTrashed()->whereNotNull($column)->where($column, '!=', '')->select(['id', $column])
                ->each(function (User $user) use ($column, &$missing) {
                    if (! filter_var($user->{$column}, FILTER_VALIDATE_URL) && ! PrivateFiles::exists($user->{$column})) {
                        $missing++;
                    }
                });
        }
        if ($missing) {
            $this->warn("{$missing} user document path(s) point to files that are on neither disk (left unchanged).");
        }
    }
}
