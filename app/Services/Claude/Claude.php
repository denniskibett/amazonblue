<?php

namespace App\Services\Claude;

use Anthropic\Client;
use Illuminate\Support\Facades\Log;

/**
 * Claude (Anthropic), the platform's one key (owner, 20 Nov 2026; ported from scharge's Assistant\Claude).
 *
 * The key and model come from Integrations › Claude (sysadmin only, encrypted, wins over .env through
 * Support\Integrations::apply), else ANTHROPIC_API_KEY / ANTHROPIC_MODEL. Claude is off until a sysadmin saves a key
 * and switches the card on. Each call asks for JSON that matches a schema, so callers never parse free text.
 */
class Claude
{
    public const DEFAULT_MODEL = 'claude-sonnet-5-5';

    public static function key(): ?string
    {
        return ((string) config('services.anthropic.key')) ?: null;
    }

    public static function model(): string
    {
        return ((string) config('services.anthropic.model')) ?: self::DEFAULT_MODEL;
    }

    public static function enabled(): bool
    {
        return (bool) config('services.anthropic.enabled') && self::key() !== null;
    }

    public static function client(?string $key = null): Client
    {
        return new Client(apiKey: $key ?? (string) self::key(), requestOptions: ['transporter' => new HttpTransporter, 'timeout' => 180]);
    }

    /**
     * One call that returns JSON matching $schema.
     *
     * @param  string|array<int, array<string, mixed>>  $content  the user turn (text, or blocks such as an image or PDF)
     * @return array{0: ?array<string, mixed>, 1: ?string} [the decoded JSON, an error a person can read]
     */
    public static function json(string $system, string|array $content, array $schema, int $maxTokens = 4000, ?string $key = null): array
    {
        $key ??= self::key();
        if (! $key) {
            return [null, 'Claude has no Anthropic key yet: a system administrator adds it on Integrations › Claude.'];
        }
        try {
            $message = self::client($key)->beta->messages->create(
                maxTokens: $maxTokens,
                model: self::model(),
                // a declined request is retried on the server's fallback model
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
                outputConfig: ['effort' => 'low', 'format' => ['type' => 'json_schema', 'schema' => $schema]],
                system: $system,
                messages: [['role' => 'user', 'content' => $content]],
            );
            if ($message->stopReason === 'refusal') {
                return [null, 'Claude declined this one. Do it by hand.'];
            }
            if ($message->stopReason === 'max_tokens') {
                return [null, 'Claude\'s answer was cut off. Try again.'];
            }
            foreach ($message->content as $block) {
                if ($block->type === 'text' && is_array($json = json_decode($block->text, true))) {
                    return [$json, null];
                }
            }

            return [null, 'Claude returned no answer.'];
        } catch (\Anthropic\Core\Exceptions\APIStatusException $e) {
            Log::warning('Claude call failed', ['type' => $e->type?->value]);

            return [null, match ($e->type?->value) {
                'authentication_error' => 'Anthropic refused the key (authentication_error). Check the key on Integrations › Claude.',
                'permission_error' => 'This Anthropic key may not use that model (permission_error).',
                'not_found_error' => 'Anthropic does not know the model "' . self::model() . '" (not_found_error).',
                'rate_limit_error', 'overloaded_error' => 'Anthropic is busy (' . $e->type->value . '). Try again in a minute.',
                default => 'Claude could not be reached (' . ($e->type?->value ?? 'API error') . ').',
            }];
        } catch (\Throwable $e) {
            Log::warning('Claude call failed', ['error' => $e->getMessage()]);

            return [null, mb_substr('Claude could not be reached: ' . $e->getMessage(), 0, 250)];
        }
    }

    /**
     * "Test connection" on the Claude card: one small real call with the key in force.
     *
     * @return array{ok: bool, message: string}
     */
    public static function test(): array
    {
        if (! self::key()) {
            return ['ok' => false, 'message' => 'No Anthropic key saved yet.'];
        }
        [$out, $error] = self::json(
            'You check that an API connection works. Answer briefly.',
            'Reply with ok = true and, in note, the words "Claude is connected".',
            ['type' => 'object', 'additionalProperties' => false, 'required' => ['ok', 'note'],
                'properties' => ['ok' => ['type' => 'boolean'], 'note' => ['type' => 'string']]],
            1000,
        );

        return $error ? ['ok' => false, 'message' => $error]
            : ['ok' => true, 'message' => 'Claude answered on ' . self::model() . ': "' . mb_substr((string) ($out['note'] ?? ''), 0, 120) . '"'];
    }
}
