<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Reads an M-Pesa confirmation SMS pasted by staff (owner, 23 Oct 2026: record a partner's investment from the SMS).
 * Handles the usual shapes:
 *   "TJN4H7K2LM Confirmed. You have received Ksh50,000.00 from JOHN DOE 0712345678 on 23/10/26 at 2:15 PM ..."
 *   "TJN4H7K2LM Confirmed. Ksh50,000.00 received from 254712345678 JOHN DOE on 23/10/26 at 2:15 PM ..."
 *   "TJN4H7K2LM Confirmed. Ksh50,000.00 sent to AIMZONE VENTURES for account 4455 on 23/10/26 at 2:15 PM ..."
 * Bank messages (bank → M-Pesa, Pesalink, bank paybill payments) are read too, with both references when the message
 * has them. Returns null when no reference or amount can be found. Nothing here trusts the SMS: staff confirm what was read.
 */
class MpesaSms
{
    public static function parse(?string $text): ?array
    {
        $t = trim(preg_replace('/\s+/', ' ', (string) $text));
        if ($t === '') {
            return null;
        }
        if ($bank = self::bank($t)) {
            return $bank;
        }
        if (! preg_match('/\b([A-Z0-9]{10})\b\.?\s*Confirmed/i', $t, $c) && ! preg_match('/^([A-Z0-9]{10})\b/', $t, $c)) {
            return null;
        }
        if (! preg_match('/K(?:sh|es)\.?\s?([\d,]+(?:\.\d{1,2})?)/i', $t, $a)) {
            return null;
        }
        $amount = round((float) str_replace(',', '', $a[1]), 2);
        if ($amount <= 0) {
            return null;
        }

        $direction = preg_match('/\b(sent to|paid to)\b/i', $t) ? 'sent' : (preg_match('/\breceived\b/i', $t) ? 'received' : null);
        $party = null;
        if (preg_match('/\b(?:from|sent to|paid to)\s+(.+?)(?:\s+for account\b|\s+on\s+\d{1,2}\/|\.\s|$)/i', $t, $p)) {
            $party = trim($p[1], " .");
        }
        $phone = null;
        if ($party && preg_match('/(\+?254|0)\s?([17]\d{2})\s?(\d{3}|\*{3})\s?(\d{3})/', $party, $ph)) {
            $phone = ($ph[1] === '0' ? '0' : '254') . $ph[2] . $ph[3] . $ph[4];
            $party = trim(str_replace($ph[0], '', $party));
        }
        $account = preg_match('/for account\s+([A-Za-z0-9\-_ ]+?)\s+on\s+\d/i', $t, $acc) ? trim($acc[1]) : null;

        $at = null;
        if (preg_match('/on\s+(\d{1,2})\/(\d{1,2})\/(\d{2,4})\s+at\s+(\d{1,2}):(\d{2})\s*(AM|PM)?/i', $t, $d)) {
            $year = strlen($d[3]) === 2 ? 2000 + (int) $d[3] : (int) $d[3];
            $hour = (int) $d[4] % 12 + (isset($d[6]) && strtoupper($d[6]) === 'PM' ? 12 : 0);
            if (! isset($d[6]) || $d[6] === '') {
                $hour = (int) $d[4];
            }
            try {
                $at = Carbon::create($year, (int) $d[2], (int) $d[1], $hour, (int) $d[5]);
            } catch (\Throwable) {
                $at = null;
            }
        }

        // M-Pesa sent to a bank's paybill: one M-Pesa code, the money lands in a bank (owner, 23 Oct 2026)
        $toBank = $direction === 'sent' && $party && preg_match(self::BANKS, $party);

        return [
            'code' => strtoupper($c[1]),
            'mpesa_ref' => strtoupper($c[1]),
            'bank_ref' => null,
            'mode' => $toBank ? 'mpesa_to_bank' : 'mpesa',
            'amount' => $amount,
            'direction' => $direction,
            'party' => $party ?: null,
            'phone' => $phone,
            'account' => $account,
            'at' => $at?->format('Y-m-d H:i'),
            'date' => $at?->toDateString(),
        ];
    }

    private const BANKS = '/\b(BANK|EQUITY|KCB|CO-?OP|COOPERATIVE|ABSA|STANBIC|NCBA|I&M|FAMILY|DTB|DIAMOND TRUST|STANDARD CHARTERED|SIDIAN|HFC|PRIME|CREDIT BANK|GULF|NBK|POSTBANK|KINGDOM)\b/i';

    /**
     * Bank messages (owner, 23 Oct 2026). A bank-to-M-Pesa transfer carries both references:
     *   "Bank to M-PESA transfer of KES 5,000.00 to 254712345678 - JOHN DOE successfully processed.
     *    Transaction Ref ID: 5690NMQS2509. M-PESA Ref ID: UJH6OA6DRB"
     *   "Pesalink transfer of KES 5,000.00 to JOHN DOE A/c 0123456789 on 23/10/2026 14:05 processed successfully. Transaction Ref ID: 276917479797"
     *   "Payment of KES 5,000.00 to 247247 on 23/10/2026 14:05 processed successfully. Transaction Ref ID: 5620GVKU8173"
     */
    private static function bank(string $t): ?array
    {
        $amount = fn ($v) => round((float) str_replace(',', '', $v), 2);
        $when = function (?string $d, ?string $h) {
            if (! $d) {
                return null;
            }
            try {
                return Carbon::createFromFormat('d/m/Y H:i', $d . ' ' . substr($h ?: '00:00', 0, 5));
            } catch (\Throwable) {
                return null;
            }
        };
        $pack = fn (string $mode, float $amt, ?string $bank, ?string $mpesa, ?string $party, ?string $phone, ?string $account, $at) => [
            'code' => $mpesa ?: $bank, 'mpesa_ref' => $mpesa, 'bank_ref' => $bank, 'mode' => $mode, 'amount' => $amt, 'direction' => 'sent',
            'party' => $party ? trim($party) : null, 'phone' => $phone, 'account' => $account, 'at' => $at?->format('Y-m-d H:i'), 'date' => $at?->toDateString(),
        ];

        if (preg_match('/Bank to M-?PESA transfer of K(?:ES|sh)\.?\s?([\d,]+(?:\.\d{1,2})?)\s*to\s*(\+?\d{9,12})\s*-\s*(.+?)\s+(?:successfully|processed|on\s)/i', $t, $m)) {
            $bank = preg_match('/Transaction Ref(?:erence)? ID:?\s*([A-Z0-9]+)/i', $t, $b) ? strtoupper($b[1]) : null;
            $mpesa = preg_match('/M-?PESA Ref(?:erence)? ID:?\s*([A-Z0-9]+)/i', $t, $x) ? strtoupper($x[1]) : null;
            $at = preg_match('/on\s+(\d{2}\/\d{2}\/\d{4})\s+([\d:]+)/', $t, $d) ? $when($d[1], $d[2]) : null;

            return ($bank || $mpesa) ? $pack('bank_to_mpesa', $amount($m[1]), $bank, $mpesa, $m[3], $m[2], null, $at) : null;
        }
        if (preg_match('/Pesalink transfer of K(?:ES|sh)\.?\s?([\d,]+(?:\.\d{1,2})?)\s+to\s+(.+?)\s+A\/c\s+([\d-]+)(?:\s+on\s+(\d{2}\/\d{2}\/\d{4})\s+([\d:]+))?.*?Transaction Ref(?:erence)? ID:?\s*([A-Z0-9]+)/i', $t, $m)) {
            return $pack('pesalink', $amount($m[1]), strtoupper($m[6]), null, $m[2], null, $m[3], $when($m[4] ?? null, $m[5] ?? null));
        }
        if (preg_match('/Payment of K(?:ES|sh)\.?\s?([\d,]+(?:\.\d{1,2})?)\s+to\s+(\d+)(?:\s+on\s+(\d{2}\/\d{2}\/\d{4})\s+([\d:]+))?.*?Transaction Ref(?:erence)? ID:?\s*([A-Z0-9]+)/i', $t, $m)) {
            $mpesa = preg_match('/M-?PESA Ref(?:erence)? ID:?\s*([A-Z0-9]+)/i', $t, $x) ? strtoupper($x[1]) : null;

            return $pack('bank_to_mpesa', $amount($m[1]), strtoupper($m[5]), $mpesa, null, null, $m[2], $when($m[3] ?? null, $m[4] ?? null));
        }

        return null;
    }
}
