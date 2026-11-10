<?php

namespace App\Support;

use App\Models\Integration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * The integrations the app talks to (owner, 3 Nov 2026) and where each .env value lands in config.
 *
 * The integrations table keeps each one's values (encrypted with APP_KEY). At boot, apply() writes the saved
 * values over the .env ones, so a change on /staff/integrations takes effect without editing .env. A switched-off
 * integration with an on/off 'switch' (M-Pesa) is turned off; the others then fall back to .env.
 * App key, database, session and cache stay in .env: they are needed before the database can be read.
 */
class Integrations
{
    private const CACHE = 'integrations.config';

    /**
     * slug => name, category, description, logo, switch (config path or null),
     * fields: ENV => [label, config path(s), type text|secret|bool|list|select, options?]
     */
    public const CATALOGUE = [
        'mpesa' => [
            'name' => 'M-Pesa (Daraja)', 'category' => 'Payments', 'logo' => 'images/integrations/mpesa.svg', 'switch' => 'lending.mpesa.enabled',
            'description' => 'Safaricom Daraja: C2B paybill payments, STK push, and B2C payouts to borrowers.',
            'fields' => [
                'MPESA_ENABLED' => ['Payments on', 'lending.mpesa.enabled', 'bool'],
                'MPESA_ENV' => ['Environment', 'lending.mpesa.env', 'select', ['sandbox', 'production']],
                'MPESA_PAYBILL' => ['Paybill shown to customers', 'lending.mpesa.paybill', 'text'],
                'MPESA_SHORTCODE' => ['Shortcode', 'lending.mpesa.shortcode', 'text'],
                'MPESA_CONSUMER_KEY' => ['Consumer key', 'lending.mpesa.consumer_key', 'secret'],
                'MPESA_CONSUMER_SECRET' => ['Consumer secret', 'lending.mpesa.consumer_secret', 'secret'],
                'MPESA_PASSKEY' => ['STK passkey', 'lending.mpesa.passkey', 'secret'],
                'MPESA_TRANSACTION_TYPE' => ['Transaction type', 'lending.mpesa.transaction_type', 'text'],
                'MPESA_ACCOUNT_FORMAT' => ['Account number format', 'lending.mpesa.account_format', 'text'],
                'MPESA_CALLBACK_BASE' => ['Callback base URL', 'lending.mpesa.callback_base', 'text'],
                'MPESA_CALLBACK_SECRET' => ['Callback secret', 'lending.mpesa.callback_secret', 'secret'],
                'MPESA_ALLOWED_IPS' => ['Allowed callback IPs (comma separated)', 'lending.mpesa.allowed_ips', 'list'],
                'MPESA_STK_ENABLED' => ['STK push on', 'lending.mpesa.stk_enabled', 'bool'],
                'MPESA_CLAIMS_ENABLED' => ['Borrower payment claims on', 'lending.mpesa.claims_enabled', 'bool'],
                'MPESA_B2C_ENABLED' => ['B2C payouts on', 'lending.mpesa.b2c_enabled', 'bool'],
                'MPESA_B2C_SHORTCODE' => ['B2C shortcode', 'lending.mpesa.b2c_shortcode', 'text'],
                'MPESA_B2C_INITIATOR' => ['B2C initiator name', 'lending.mpesa.b2c_initiator', 'text'],
                'MPESA_B2C_SECURITY_CREDENTIAL' => ['B2C security credential', 'lending.mpesa.b2c_security_credential', 'secret'],
                'MPESA_B2C_COMMAND' => ['B2C command', 'lending.mpesa.b2c_command', 'text'],
            ],
        ],
        // Sharet Pay, a second rail beside the own paybill above (9 Nov 2026; docs/SHARET_PAYMENTS.md). Its card is the one
        // place for the key (connection fields) and the rail's options; the card's switch alone turns it on and off
        // ('switch_on'), "Test connection" checks the key and registers the webhook, and Remove forgets the key.
        'sharet' => [
            'name' => 'Sharet Pay', 'category' => 'Payments', 'logo' => 'images/integrations/sharet.svg', 'switch' => 'sharet.enabled',
            'switch_on' => true, 'removable' => true, 'connection' => ['SHARET_API_KEY', 'SHARET_WEBHOOK_SECRET', 'SHARET_BASE_URL'],
            'description' => 'Borrowers repay through a Sharet pay link into our Sharet wallet; optionally, approved disbursements and partner payouts go out through Sharet.',
            'fields' => [
                'SHARET_API_KEY' => ['API key', 'sharet.api_key', 'secret'],
                'SHARET_WEBHOOK_SECRET' => ['Webhook signing secret', 'sharet.webhook_secret', 'secret'],
                'SHARET_BASE_URL' => ['API base URL', 'sharet.base_url', 'text'],
                'SHARET_REPAYMENT_RAIL' => ['Repayment rail shown first to borrowers', 'sharet.repayment_rail', 'select', ['paybill', 'sharet']],
                'SHARET_PAYOUTS_ENABLED' => ['Disbursements and partner payouts through Sharet', 'sharet.payouts_enabled', 'bool'],
                'SHARET_PAY_LINK_MINUTES' => ['Pay link open for (minutes)', 'sharet.pay_link_minutes', 'text'],
            ],
            'help' => [
                'SHARET_API_KEY' => 'From Sharet › Developers › API keys: sk_test_… or sk_live_…, with scopes collect, payout and read. Encrypted when saved and never shown again; leave blank to keep the saved key.',
                'SHARET_WEBHOOK_SECRET' => 'Optional. Test connection registers our webhook with Sharet and fills this in.',
                'SHARET_BASE_URL' => 'Optional. Blank uses https://sharet.africa/api/v1.',
                'SHARET_REPAYMENT_RAIL' => 'Both are shown while Sharet Pay is on; this one comes first.',
                'SHARET_PAYOUTS_ENABLED' => 'Sent at 08:00, 12:00 and 16:00, after any second sign-off. Keep off until collections are trusted.',
                'SHARET_PAY_LINK_MINUTES' => '4320 is 3 days. The amount owed changes with penalties, so keep it short.',
            ],
            'placeholders' => ['SHARET_API_KEY' => 'sk_live_…', 'SHARET_WEBHOOK_SECRET' => 'whsec_…', 'SHARET_BASE_URL' => 'https://sharet.africa/api/v1'],
        ],
        'africastalking' => [
            'name' => "Africa's Talking", 'category' => 'SMS', 'logo' => 'images/integrations/africastalking.svg', 'switch' => null,
            'description' => 'Bulk SMS for reminders, receipts and alerts (used when the SMS driver is africastalking).',
            'fields' => [
                'SMS_DRIVER' => ['SMS driver (log, africastalking, kenyasms)', 'lending.notifications.sms.driver', 'select', ['log', 'africastalking', 'kenyasms']],
                'AT_USERNAME' => ['Username', 'lending.notifications.sms.africastalking.username', 'text'],
                'AT_API_KEY' => ['API key', 'lending.notifications.sms.africastalking.api_key', 'secret'],
                'AT_SENDER_ID' => ['Sender ID', 'lending.notifications.sms.africastalking.sender_id', 'text'],
            ],
        ],
        'kenyasms' => [
            'name' => 'KenyaSMS', 'category' => 'SMS', 'logo' => 'images/integrations/kenyasms.png', 'switch' => null,
            'description' => 'Kenyan bulk SMS gateway (used when the SMS driver is kenyasms). How their API takes a message is set here.',
            'fields' => [
                'KENYASMS_URL' => ['API URL', 'lending.notifications.sms.kenyasms.url', 'text'],
                'KENYASMS_API_KEY' => ['API key', 'lending.notifications.sms.kenyasms.api_key', 'secret'],
                'KENYASMS_SENDER_ID' => ['Sender ID', 'lending.notifications.sms.kenyasms.sender_id', 'text'],
                'KENYASMS_AUTH' => ['Where the key goes (bearer, header:Name, field:name)', 'lending.notifications.sms.kenyasms.auth', 'text'],
                'KENYASMS_FORMAT' => ['Body format', 'lending.notifications.sms.kenyasms.format', 'select', ['json', 'form']],
                'KENYASMS_FIELD_TO' => ['Phone field name', 'lending.notifications.sms.kenyasms.field_to', 'text'],
                'KENYASMS_FIELD_MESSAGE' => ['Message field name', 'lending.notifications.sms.kenyasms.field_message', 'text'],
                'KENYASMS_FIELD_SENDER' => ['Sender field name', 'lending.notifications.sms.kenyasms.field_sender', 'text'],
                'KENYASMS_PHONE_FORMAT' => ['Phone format', 'lending.notifications.sms.kenyasms.phone_format', 'text'],
            ],
        ],
        'mail' => [
            'name' => 'Email (SMTP)', 'category' => 'Email', 'logo' => 'images/integrations/mail.svg', 'switch' => null,
            'description' => 'Outgoing email: sign-in codes, KYC, receipts, money sent, reminders and staff alerts.',
            'fields' => [
                'MAIL_MAILER' => ['Mailer', 'mail.default', 'select', ['smtp', 'log', 'sendmail']],
                // when set it wins over host, port, username and password (live: the server's own relay)
                'MAIL_URL' => ['SMTP URL (overrides the fields below when set)', 'mail.mailers.smtp.url', 'secret'],
                'MAIL_HOST' => ['SMTP host', 'mail.mailers.smtp.host', 'text'],
                'MAIL_PORT' => ['SMTP port', 'mail.mailers.smtp.port', 'text'],
                'MAIL_SCHEME' => ['Scheme (smtps for 465, blank for 587)', 'mail.mailers.smtp.scheme', 'text'],
                'MAIL_USERNAME' => ['Username', 'mail.mailers.smtp.username', 'text'],
                'MAIL_PASSWORD' => ['Password', 'mail.mailers.smtp.password', 'secret'],
                'MAIL_FROM_ADDRESS' => ['From address', ['mail.from.address', 'backup.notifications.mail.from.address'], 'text'],
                'MAIL_FROM_NAME' => ['From name', ['mail.from.name', 'backup.notifications.mail.from.name'], 'text'],
            ],
        ],
        'google-analytics' => [
            'name' => 'Google Analytics', 'category' => 'Analytics', 'logo' => 'images/integrations/google-analytics.svg', 'switch' => null,
            'description' => 'Visitor analytics on the public website.',
            'fields' => [
                'GOOGLE_ANALYTICS_ID' => ['Measurement ID (G-…)', 'services.google.analytics_id', 'text'],
            ],
        ],
        'aws-s3' => [
            'name' => 'Amazon S3', 'category' => 'Storage', 'logo' => 'images/integrations/aws-s3.svg', 'switch' => null,
            'description' => 'File storage for documents and backups when the s3 disk is used.',
            'fields' => [
                'AWS_ACCESS_KEY_ID' => ['Access key ID', ['filesystems.disks.s3.key', 'services.ses.key'], 'secret'],
                'AWS_SECRET_ACCESS_KEY' => ['Secret access key', ['filesystems.disks.s3.secret', 'services.ses.secret'], 'secret'],
                'AWS_DEFAULT_REGION' => ['Region', ['filesystems.disks.s3.region', 'services.ses.region'], 'text'],
                'AWS_BUCKET' => ['Bucket', 'filesystems.disks.s3.bucket', 'text'],
            ],
        ],
        'slack' => [
            'name' => 'Slack', 'category' => 'Alerts', 'logo' => 'images/integrations/slack.svg', 'switch' => null,
            'description' => 'Error alerts from the log and staff notifications to a Slack channel.',
            'fields' => [
                'LOG_SLACK_WEBHOOK_URL' => ['Log webhook URL', 'logging.channels.slack.url', 'secret'],
                'SLACK_BOT_USER_OAUTH_TOKEN' => ['Bot token', 'services.slack.notifications.bot_user_oauth_token', 'secret'],
                'SLACK_BOT_USER_DEFAULT_CHANNEL' => ['Default channel', 'services.slack.notifications.channel', 'text'],
            ],
        ],
        // I&M On The Go is a banking app with no public API, so this is the collection account itself. It is the
        // partner-funding account, edited only on /system (super-admin, password, audited, admins emailed), so it is
        // read-only here: 'managed' names that page; field paths are Support\PartnerFunding keys (owner, 3 Nov 2026).
        'imbank' => [
            'name' => 'I&M Bank account', 'category' => 'Banking', 'logo' => 'images/integrations/imbank.png', 'switch' => null, 'managed' => 'system.index',
            'description' => 'Collection account (I&M On The Go): partner funds by bank transfer, Pesalink or M-Pesa paybill to the account.',
            'fields' => [
                'PARTNER_FUNDING_BANK_NAME' => ['Bank', 'bank_name', 'text'],
                'PARTNER_FUNDING_BANK_ACCOUNT_NAME' => ['Account name', 'bank_account_name', 'text'],
                'PARTNER_FUNDING_BANK_ACCOUNT_NUMBER' => ['Account number', 'bank_account_number', 'text'],
                'PARTNER_FUNDING_BANK_BRANCH' => ['Branch', 'bank_branch', 'text'],
                'PARTNER_FUNDING_BANK_SWIFT' => ['SWIFT code', 'bank_swift', 'text'],
                'PARTNER_FUNDING_MPESA_PAYBILL' => ['M-Pesa paybill', 'mpesa_paybill', 'text'],
                'PARTNER_FUNDING_MPESA_ACCOUNT' => ['M-Pesa account no.', 'mpesa_account', 'text'],
            ],
        ],
    ];

    /** Writes the saved values over the .env ones. Never throws: a broken row leaves .env in charge. */
    public static function apply(): void
    {
        try {
            $rows = Cache::rememberForever(self::CACHE, function () {
                if (! Schema::hasTable('integrations')) {
                    return [];
                }

                return Integration::all()->map(fn ($i) => ['slug' => $i->slug, 'enabled' => $i->enabled, 'settings' => $i->settings ?? []])->all();
            });
        } catch (\Throwable $e) {
            Log::warning('Integrations not applied', ['error' => $e->getMessage()]);

            return;
        }
        foreach ($rows as $row) {
            $def = self::CATALOGUE[$row['slug']] ?? null;
            if (! $def || ! empty($def['managed'])) {
                continue;
            }
            if (! self::on($row['slug'], $row['enabled'], $row['settings'])) {
                if ($def['switch']) {
                    config([$def['switch'] => false]);
                }
                continue;
            }
            if (! empty($def['switch_on'])) {
                config([$def['switch'] => true]);
            }
            foreach ($def['fields'] as $env => $field) {
                if (! array_key_exists($env, $row['settings']) || $row['settings'][$env] === null || $row['settings'][$env] === '') {
                    continue;
                }
                foreach ((array) $field[1] as $path) {
                    config([$path => self::cast($row['settings'][$env], $field[2])]);
                }
            }
        }
    }

    /**
     * Whether an integration is switched on. Before the card's switch was its only on/off (10 Nov 2026), Sharet Pay
     * also had a "Sharet Pay on" field (SHARET_ENABLED); a saved "off" there still counts until the switch is used.
     */
    public static function on(string $slug, bool $enabled, array $settings): bool
    {
        return $enabled && ! ($slug === 'sharet' && ($settings[self::SHARET_LEGACY_SWITCH] ?? null) === '0');
    }

    /** Where the last "Test connection" is kept in an integration's saved settings: [at, ok, message]. */
    public const TEST = '_test';

    /** The old Sharet "on" field; dropped from the saved settings whenever the Sharet card is saved or switched. */
    public const SHARET_LEGACY_SWITCH = 'SHARET_ENABLED';

    /** Saved values of one integration (decrypted), or [] when it was never saved. */
    public static function saved(string $slug): array
    {
        return Integration::where('slug', $slug)->first()?->settings ?? [];
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE);
    }

    /** The value in force now, as the form shows it (lists joined with commas, booleans as 1/0). */
    public static function current(string $slug, string $env): ?string
    {
        $def = self::CATALOGUE[$slug];
        $path = ((array) $def['fields'][$env][1])[0];
        $value = ! empty($def['managed']) ? (PartnerFunding::all()[$path] ?? null) : config($path);

        return match (true) {
            is_array($value) => implode(',', $value),
            is_bool($value) => $value ? '1' : '0',
            $value === null => null,
            default => (string) $value,
        };
    }

    public static function mask(?string $value): string
    {
        if ($value === null || $value === '') {
            return 'Not set';
        }

        return str_repeat('•', 8) . (mb_strlen($value) > 8 ? mb_substr($value, -4) : '');
    }

    private static function cast(string $value, string $type): mixed
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'list' => array_values(array_filter(array_map('trim', explode(',', $value)))),
            default => $value,
        };
    }
}
