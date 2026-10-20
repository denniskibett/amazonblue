<?php

namespace App\Console\Commands;

use App\Services\Mpesa\Daraja;
use Illuminate\Console\Command;

/** Registers this site's C2B validation and confirmation URLs with Safaricom for the configured shortcode. */
class MpesaRegisterUrls extends Command
{
    protected $signature = 'mpesa:register-urls';

    protected $description = 'Register the C2B callback URLs with Daraja (run once per shortcode, after setting the keys)';

    public function handle(Daraja $daraja): int
    {
        if ($p = $daraja->problem()) {
            $this->error($p);

            return self::FAILURE;
        }
        $this->line('Environment: ' . $daraja->env() . ', shortcode ' . config('lending.mpesa.shortcode'));
        $this->line('Confirmation: ' . preg_replace('#/mpesa/[^/]+/#', '/mpesa/<secret>/', $daraja->callbackUrl('c2b/confirmation')));
        $this->line(json_encode($daraja->registerC2bUrls()));

        return self::SUCCESS;
    }
}
