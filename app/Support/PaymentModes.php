<?php

namespace App\Support;

/**
 * How money moved, and its references (owner, 23 Oct 2026).
 * A transfer between a bank and M-Pesa has two references, one on each side: the bank's Transaction Ref ID and the
 * M-Pesa code. Both are kept (bank_ref, mpesa_ref) so either statement can be matched and neither is recorded twice.
 * `transaction` stays the reference staff search by: for money we send (disbursements) the bank reference when it
 * left a bank, else the M-Pesa code; for money we receive (repayments) the M-Pesa code when there is one.
 */
class PaymentModes
{
    public const MODES = [
        'mpesa' => 'M-Pesa',
        'bank_to_mpesa' => 'Bank → M-Pesa',
        'mpesa_to_bank' => 'M-Pesa → bank',
        'bank_transfer' => 'Bank transfer',
        'pesalink' => 'Pesalink',
        'cash' => 'Cash',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ];

    /** Modes where the money crossed between a bank and M-Pesa: both references are asked for. */
    public const BOTH = ['bank_to_mpesa', 'mpesa_to_bank'];

    public const BANK = ['bank_transfer', 'pesalink', 'cheque'];

    public static function label(?string $mode): string
    {
        return self::MODES[self::normalise($mode)] ?? ucfirst(str_replace('_', ' ', (string) $mode));
    }

    /** Old spellings on live ("Mpesa", "Bank") read as the current ones. */
    public static function normalise(?string $mode): ?string
    {
        $m = strtolower(trim((string) $mode));

        return match ($m) {
            '' => null,
            'mpesa', 'm-pesa', 'm_pesa' => 'mpesa',
            'bank', 'bank transfer', 'bank_transfer', 'eft', 'rtgs' => 'bank_transfer',
            default => $m,
        };
    }

    /** What a reference looks like: an M-Pesa code (10 characters, starts with a letter) or a bank reference. */
    public static function shape(?string $ref): ?string
    {
        $r = strtoupper(trim((string) $ref));
        if ($r === '' || str_contains($r, ' ')) {
            return null;
        }
        if (preg_match('/^[A-Z][A-Z0-9]{9}$/', $r) && preg_match('/\d/', $r)) {
            return 'mpesa';
        }
        if (preg_match('/^\d{4}[A-Z]{4}\d{4}$/', $r) || preg_match('/^\d{8,16}$/', $r) || preg_match('/^(FT|TT|PL|EFT|RTGS)[A-Z0-9]{6,}$/', $r)) {
            return 'bank';
        }

        return null;
    }

    /**
     * Fill bank_ref / mpesa_ref from the mode and the reference typed, without overwriting what was given.
     * $direction: 'out' (we send) or 'in' (we receive). Returns [transaction, bank_ref, mpesa_ref].
     */
    public static function refs(string $direction, ?string $mode, ?string $transaction, ?string $bankRef, ?string $mpesaRef): array
    {
        $mode = self::normalise($mode);
        $t = trim((string) $transaction) ?: null;
        $bank = trim((string) $bankRef) ?: null;
        $mpesa = strtoupper(trim((string) $mpesaRef)) ?: null;
        if ($t && ! $bank && ! $mpesa) {
            $shape = self::shape($t);
            if ($shape === 'mpesa' && ! in_array($mode, self::BANK, true)) {
                $mpesa = strtoupper($t);
            } elseif ($shape === 'bank' || in_array($mode, self::BANK, true)) {
                $bank = $shape === 'bank' || in_array($mode, self::BANK, true) ? $t : null;
            }
        }
        if (! $t) {
            $t = $direction === 'out' ? ($bank ?: $mpesa) : ($mpesa ?: $bank);
        }

        return [$t, $bank, $mpesa];
    }

    /**
     * The mode and references from a staff form, checked (owner, 23 Oct 2026):
     *  - bank ↔ M-Pesa needs both references;
     *  - a reference already recorded on the same loan is refused (a second entry of the same money).
     * Returns the fields to save: mode, transaction, and bank_ref / mpesa_ref once those columns exist.
     */
    public static function fromRequest(\Illuminate\Http\Request $request, string $direction, string $table, ?int $loanId, ?int $ignoreId = null): array
    {
        $mode = self::normalise($request->input('mode'));
        $given = trim((string) $request->input('transaction'));
        [$t, $bank, $mpesa] = self::refs($direction, $mode, $given, $request->input('bank_ref'), $request->input('mpesa_ref'));
        if (in_array($mode, self::BOTH, true) && (! $bank || ! $mpesa)) {
            throw \Illuminate\Validation\ValidationException::withMessages([$bank ? 'mpesa_ref' : 'bank_ref' =>
                self::label($mode) . ' needs both references: the bank\'s Transaction Ref ID and the M-Pesa code.']);
        }
        $kind = $request->input('kind') ?: \App\Services\CustomerWallets::kind($t);
        $refs = array_values(array_unique(array_filter([$t, $bank, $mpesa])));
        if ($loanId && $refs && $kind === 'cash') {
            $hasCols = \Illuminate\Support\Facades\Schema::hasColumn($table, 'mpesa_ref');
            $dup = \Illuminate\Support\Facades\DB::table($table)->where('loan_id', $loanId)->whereNull('deleted_at')
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where(fn ($q) => $q->whereIn('transaction', $refs)->when($hasCols, fn ($w) => $w->orWhereIn('bank_ref', $refs)->orWhereIn('mpesa_ref', $refs)))
                ->value('id');
            if ($dup) {
                throw \Illuminate\Validation\ValidationException::withMessages(['transaction' =>
                    'This reference is already recorded on this loan (#' . $dup . '). Check you are not entering the same money twice.']);
            }
        }
        $out = ['mode' => $mode, 'transaction' => $t];
        if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'mpesa_ref')) {
            $out += ['bank_ref' => $bank, 'mpesa_ref' => $mpesa];
        }

        return $out;
    }
}
