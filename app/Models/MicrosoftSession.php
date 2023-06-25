<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MicrosoftSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session',
        'user_id',
    ];

    // ATTRIBUTES
    public function getUserAttribute(): User
    {
        return $this->user()->first();
    }

    // RELATIONS
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
