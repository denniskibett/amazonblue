<?php

namespace App\Console\Commands;

use App\Http\Controllers\MobileAppController;
use Illuminate\Console\Command;

/**
 * Puts an APK up on the download page from the server (owner, 15 Nov 2026): the same as the API keys page's upload,
 * for files bigger than PHP's upload limit.
 *
 *   php artisan mobile:apk /root/aimzone-test.apk --app-version=0.1.0 --notes="First test build"
 */
class MobileApk extends Command
{
    protected $signature = 'mobile:apk {file : the APK on this server} {--app-version= : e.g. 0.1.0} {--notes= : what changed}';

    protected $description = 'Put an APK up on the /app download page';

    public function handle(): int
    {
        try {
            $meta = MobileAppController::put((string) $this->argument('file'), (string) $this->option('app-version'), $this->option('notes'));
        } catch (\DomainException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info('Version ' . $meta['version'] . ' is up: ' . number_format($meta['size'] / 1048576, 1) . ' MB, SHA-256 ' . $meta['sha256']);

        return self::SUCCESS;
    }
}
