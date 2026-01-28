<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sequence extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'status',
        'entry_filter',
    ];

    protected $casts = [
        'entry_filter' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(SequenceStep::class)->orderBy('step_order');
    }

    public function sequenceContacts(): HasMany
    {
        return $this->hasMany(SequenceContact::class);
    }

    public function activeContacts(): HasMany
    {
        return $this->hasMany(SequenceContact::class)->where('status', 'active');
    }
}
