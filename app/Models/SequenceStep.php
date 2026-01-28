<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SequenceStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'sequence_id',
        'step_order',
        'name',
        'subject',
        'content',
        'delay_days',
        'send_time',
    ];

    protected $casts = [
        'send_time' => 'datetime:H:i',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    public function sequenceEmails(): HasMany
    {
        return $this->hasMany(SequenceEmail::class);
    }
}
