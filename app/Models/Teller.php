<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teller extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'branch'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }}
