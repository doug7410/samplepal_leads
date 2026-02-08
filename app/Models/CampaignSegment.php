<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignSegment extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'campaign_id',
        'name',
        'position',
        'subject',
        'content',
        'status',
        'sent_at',
        'completed_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function campaignContacts(): HasMany
    {
        return $this->hasMany(CampaignContact::class);
    }

    public function getEffectiveSubject(): string
    {
        return $this->subject ?? $this->campaign->subject;
    }

    public function getEffectiveContent(): string
    {
        return $this->content ?? $this->campaign->content;
    }
}
