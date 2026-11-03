<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A key another system uses to call /api/v1 (owner, 3 Nov 2026). Only its SHA-256 hash is stored. */
class ApiKey extends Model
{
    public const PREFIX = 'azk_live_';

    protected $fillable = ['name', 'prefix', 'token_hash', 'last_four', 'status', 'created_by', 'last_used_at', 'last_used_ip'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }

    /** Makes a key; returns [model, the plain key to show once]. */
    public static function issue(string $name, ?int $by): array
    {
        $plain = self::PREFIX . Str::random(40);

        return [self::create(['name' => $name, 'prefix' => self::PREFIX, 'token_hash' => hash('sha256', $plain),
            'last_four' => substr($plain, -4), 'status' => 'active', 'created_by' => $by]), $plain];
    }

    /** Replaces the secret of an existing key; returns the new plain key. */
    public function regenerate(): string
    {
        $plain = self::PREFIX . Str::random(40);
        $this->update(['token_hash' => hash('sha256', $plain), 'last_four' => substr($plain, -4), 'last_used_at' => null, 'last_used_ip' => null]);

        return $plain;
    }

    public static function findActive(?string $plain): ?self
    {
        return $plain ? self::where('token_hash', hash('sha256', $plain))->where('status', 'active')->first() : null;
    }

    public function masked(): string
    {
        return $this->prefix . str_repeat('•', 10) . $this->last_four;
    }
}
