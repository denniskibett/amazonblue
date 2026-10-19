<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\System;
use App\Helpers\SystemHelper;
use App\Helpers\CountryHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\AuditTrail;
use App\Support\CustomerNotifier;
use App\Support\PartnerFunding;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class SystemController extends Controller
{
    public function index()
    {
    $system = System::settings();
    $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
    
    // Get currencies from CountryHelper
    try {
        $currencies = CountryHelper::currencyOptions(false);
    } catch (\Exception $e) {
        // Fallback currencies
        $currencies = [
            'KES' => 'Kenyan Shilling (KSh)',
            'USD' => 'US Dollar ($)',
            'EUR' => 'Euro (€)',
            'GBP' => 'British Pound (£)',
            'JPY' => 'Japanese Yen (¥)',
            'CAD' => 'Canadian Dollar (C$)',
        ];
    }

        // Get countries for location - use countryOptions() method
        try {
            $countries = CountryHelper::countryOptions(false);
        } catch (\Exception $e) {
            $countries = [
                'KE' => 'Kenya',
                'US' => 'United States',
                'GB' => 'United Kingdom',
                'CA' => 'Canada',
            ];
        }

        // Get all social media platforms from system settings
        $socialPlatforms = [
            'facebook' => ['color' => '#1877F2', 'icon' => 'ri-facebook-fill'],
            'twitter' => ['color' => '#1DA1F2', 'icon' => 'ri-twitter-fill'],
            'instagram' => ['color' => '#E4405F', 'icon' => 'ri-instagram-fill'],
            'linkedin' => ['color' => '#0A66C2', 'icon' => 'ri-linkedin-fill'],
            'youtube' => ['color' => '#FF0000', 'icon' => 'ri-youtube-fill'],
            'whatsapp' => ['color' => '#25D366', 'icon' => 'ri-whatsapp-fill'],
            'tiktok' => ['color' => '#000000', 'icon' => 'ri-tiktok-fill'],
            'telegram' => ['color' => '#26A5E4', 'icon' => 'ri-telegram-fill'],
            'github' => ['color' => '#181717', 'icon' => 'ri-github-fill'],
            'discord' => ['color' => '#5865F2', 'icon' => 'ri-discord-fill'],
            'slack' => ['color' => '#4A154B', 'icon' => 'ri-slack-fill'],
            'reddit' => ['color' => '#FF4500', 'icon' => 'ri-reddit-fill'],
            'pinterest' => ['color' => '#BD081C', 'icon' => 'ri-pinterest-fill'],
            'snapchat' => ['color' => '#FFFC00', 'icon' => 'ri-snapchat-fill'],
            'skype' => ['color' => '#00AFF0', 'icon' => 'ri-skype-fill'],
        ];

        return view('admin.system.simple', compact(
            'system', 
            'timezones', 
            'currencies',
            'countries',
            'socialPlatforms'
        ));
    }


    public function update(Request $request)
    {
        $system = System::settings();

        $request->validate([
            'name' => 'required|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'timezone' => 'required|string',
            'date_format' => 'required|string',
            'currency' => 'required|string|max:3',
            'currency_symbol' => 'required|string|max:10',
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'pagination_limit' => 'required|integer|min:5|max:100',
            // Allow both image and SVG files
            'logo' => 'nullable|mimes:jpg,jpeg,png,gif,svg,webp|max:2048',
            'logo_dark' => 'nullable|mimes:jpg,jpeg,png,gif,svg,webp|max:2048',
            'logo_icon' => 'nullable|mimes:jpg,jpeg,png,gif,svg,webp|max:1024',
            'favicon' => 'nullable|mimes:jpg,jpeg,png,gif,svg,ico,webp|max:1024',
        ]);

        // Collect all normal inputs
        $data = $request->only([
            'name', 'slogan', 'timezone', 'date_format', 'time_format',
            'currency', 'currency_symbol', 'primary_color', 'secondary_color',
            'contact_email', 'contact_phone', 'address', 'meta_description',
            'meta_keywords', 'pagination_limit', 'custom_css', 'custom_js'
        ]);

        // Handle location data
        if ($request->has('location_country') || $request->has('location_city')) {
            $location = [
                'country' => $request->input('location_country', ''),
                'city' => $request->input('location_city', ''),
                'name' => $request->input('location_name', ''),
                'latitude' => $request->input('location_latitude', ''),
                'longitude' => $request->input('location_longitude', ''),
            ];
            $data['location'] = json_encode($location);
        }

        // Handle image uploads - CORRECTED PATH
        $imageFields = [
            'logo' => 'images/logo',
            'logo_dark' => 'images/logo',
            'logo_icon' => 'images/logo',
            'favicon' => 'images',
        ];

        foreach ($imageFields as $field => $folder) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                
                // Get original extension
                $extension = $file->getClientOriginalExtension();
                
                // Generate unique filename
                $filename = $field .'.' . $extension;
                
                // Define storage path
                $path = "public/{$folder}";
                
                // Ensure directory exists
                if (!Storage::exists($path)) {
                    Storage::makeDirectory($path, 0755, true);
                }
                
                // Delete old file if exists
                if ($system->$field && Storage::exists('public/' . $system->$field)) {
                    Storage::delete('public/' . $system->$field);
                }
                
                // Store new file
                $storedPath = $file->storeAs($folder, $filename, 'public');
                
                // Store relative path in database (images/logo/filename.ext)
                $data[$field] = $storedPath;
            }
        }
        $system->update($data);
        Cache::forget('system_settings');
        SystemHelper::clearCache();
        return back()->with('success', 'System settings updated successfully!');
    }   
    /**
     * Partner funding details (where partners send money): super-admin only (route), current password required,
     * audited and emailed to every admin by PartnerFunding / CustomerNotifier. An empty field falls back to config.
     */
    public function updatePartnerFunding(Request $request)
    {
        $data = $request->validateWithBag('partnerFunding', [
            'current_password' => ['required', 'current_password'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:150'],
            'bank_account_number' => ['nullable', 'string', 'max:40', 'regex:/^[0-9A-Za-z\- ]+$/'],
            'bank_branch' => ['nullable', 'string', 'max:100'],
            'bank_swift' => ['nullable', 'string', 'regex:/^[A-Za-z]{6}[A-Za-z0-9]{2}([A-Za-z0-9]{3})?$/'],
            'mpesa_paybill' => ['nullable', 'digits_between:5,7'],
            'mpesa_account' => ['nullable', 'string', 'max:40', 'regex:/^[0-9A-Za-z\-]+$/'],
            'reference_prefix' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9\-]+$/'],
            'min_amount' => ['nullable', 'integer', 'min:1', 'max:100000000'],
        ], [
            'current_password.current_password' => 'That is not your current password.',
            'bank_swift.regex' => 'A SWIFT code has 8 or 11 letters and numbers.',
            'mpesa_paybill.digits_between' => 'A paybill number has 5 to 7 digits.',
        ]);

        $result = PartnerFunding::save($data);
        if (! $result['changed']) {
            return redirect(route('system.index') . '#partner-funding')->with('success', 'Partner funding details: nothing changed.');
        }
        CustomerNotifier::partnerFundingChanged($request->user(), $result['old'], $result['new']);

        return redirect(route('system.index') . '#partner-funding')
            ->with('success', 'Partner funding details saved. Every admin has been emailed about the change.');
    }

    public function clearCache()
    {
        Cache::flush();

        
        SystemHelper::clearCache();
        return back()->with('success', 'Cache cleared successfully!');
    }

    public function toggleMaintenance()
    {
        $system = System::settings();
        $system->update([
            'maintenance_mode' => !$system->maintenance_mode
        ]);
        
        Cache::forget('system_settings');
        SystemHelper::clearCache();
        return back()->with('success', 'Maintenance mode updated!');
    }

    /**
     * Download a fresh database backup (mysqldump, gzipped). The file is written to
     * storage/app/private/backups (never public) and streamed to the admin. Credentials go to
     * mysqldump through a temporary 0600 option file, never the command line, and no error shown
     * to the user contains connection details.
     */
    public function backupDatabase()
    {
        // Access: the system.* route group (admins / super-admins only).
        $connection = config('database.default');
        $db = config("database.connections.{$connection}");
        if (! in_array($db['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            return back()->with('error', 'Backups are only available for MySQL/MariaDB databases.');
        }

        $dir = storage_path('app/private/backups');
        $optionFile = null;
        $sqlPath = null;

        try {
            if (! is_dir($dir)) {
                mkdir($dir, 0750, true);
            }
            $finder = new ExecutableFinder();
            $binary = $finder->find('mysqldump', null, ['/usr/bin', '/usr/local/bin', '/usr/local/mysql/bin', '/opt/homebrew/bin'])
                ?? $finder->find('mariadb-dump', null, ['/usr/bin', '/usr/local/bin']);
            if (! $binary) {
                throw new \RuntimeException('mysqldump not found on this server');
            }

            $optionFile = tempnam($dir, '.dump-');
            chmod($optionFile, 0600);
            $quote = fn ($v) => '"' . addcslashes((string) $v, "\\\"") . '"';
            $lines = ['[client]', 'user=' . $quote($db['username'] ?? ''), 'password=' . $quote($db['password'] ?? '')];
            if (! empty($db['unix_socket'])) {
                $lines[] = 'socket=' . $quote($db['unix_socket']);
            } else {
                $lines[] = 'host=' . $quote($db['host'] ?? '127.0.0.1');
                $lines[] = 'port=' . (int) ($db['port'] ?? 3306);
            }
            file_put_contents($optionFile, implode("\n", $lines) . "\n");

            $name = 'backup-' . now()->format('Ymd-His');
            $sqlPath = "{$dir}/{$name}.sql";
            $process = new Process([
                $binary, "--defaults-extra-file={$optionFile}",
                '--single-transaction', '--routines', '--triggers', '--no-tablespaces',
                "--result-file={$sqlPath}", $db['database'],
            ]);
            $process->setTimeout(600);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($sqlPath) || filesize($sqlPath) === 0) {
                Log::error('Database backup failed', ['exit' => $process->getExitCode(), 'error' => mb_substr($process->getErrorOutput(), 0, 500)]);
                throw new \RuntimeException('mysqldump did not complete');
            }

            $gzPath = "{$sqlPath}.gz";
            $in = fopen($sqlPath, 'rb');
            $out = gzopen($gzPath, 'wb6');
            while (! feof($in)) {
                gzwrite($out, fread($in, 1024 * 1024));
            }
            fclose($in);
            gzclose($out);
            @unlink($sqlPath);
            chmod($gzPath, 0640);

            AuditTrail::record('system', 0, 'create', null, ['event' => 'database_backup', 'file' => basename($gzPath), 'bytes' => filesize($gzPath)]);

            return response()->download($gzPath, basename($gzPath), ['Content-Type' => 'application/gzip']);
        } catch (\Throwable $e) {
            Log::error('Database backup failed: ' . get_class($e) . ': ' . $e->getMessage());
            if ($sqlPath && is_file($sqlPath)) {
                @unlink($sqlPath);
            }

            return back()->with('error', 'Backup failed. Details are in the application log.');
        } finally {
            if ($optionFile && is_file($optionFile)) {
                @unlink($optionFile);
            }
        }
    }
}
