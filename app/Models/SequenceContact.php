<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SequenceContact extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_EXITED = 'exited';

    public const EXIT_REASON_CONVERTED = 'converted';

    public const EXIT_REASON_UNSUBSCRIBED = 'unsubscribed';

    public const EXIT_REASON_MANUAL = 'manual';

    protected $fillable = [
        'sequence_id',
        'contact_id',
        'current_step',
        'status',
        'next_send_at',
        'entered_at',
        'exited_at',
        'exit_reason',
    ];

    protected $casts = [
        'next_send_at' => 'datetime',
        'entered_at' => 'datetime',
        'exited_at' => 'datetime',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function sequenceEmails(): HasMany
    {
        return $this->hasMany(SequenceEmail::class);
    }
}
