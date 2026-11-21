<?php

namespace Tests\Concerns;

use App\Services\KycHold;

/**
 * For tests of flows that start from a fully verified client: every client counts as KYC-complete, so the on-hold
 * screen and the kyc.clear middleware (owner, 21 Nov 2026) stay out of the way. The hold itself is tested in
 * KycHoldAndMobileRolesTest.
 */
trait ClearsKycHold
{
    protected function clearKycHold(): void
    {
        $this->partialMock(KycHold::class, fn ($m) => $m->shouldReceive('status')->andReturn([
            'state' => 'clear', 'sections' => [], 'done' => 4, 'total' => 4, 'next' => '/kyc/review', 'notes' => null, 'emailConfirmed' => true,
        ]));
    }
}
