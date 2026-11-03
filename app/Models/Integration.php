<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One integration (Support\Integrations::CATALOGUE); settings are its .env values, encrypted with APP_KEY. */
class Integration extends Model
{
    protected $fillable = ['slug', 'name', 'category', 'description', 'logo', 'enabled', 'settings', 'updated_by'];

    protected $hidden = ['settings'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'settings' => 'encrypted:array'];
    }
}
