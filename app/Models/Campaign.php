<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_FAILED = 'failed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'description',
        'subject',
        'content',
        'from_email',
        'from_name',
        'reply_to',
        'status',
        'scheduled_at',
        'completed_at',
        'user_id',
        'filter_criteria',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'filter_criteria' => 'json',
    ];

    /**
     * Get the user that created the campaign.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the contacts associated with this campaign.
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'campaign_contacts')
            ->withPivot('status', 'message_id', 'sent_at', 'delivered_at', 'opened_at',
                'clicked_at', 'responded_at', 'failed_at', 'failure_reason')
            ->withTimestamps();
    }

    /**
     * Get the email events for this campaign.
     */
    public function emailEvents(): HasMany
    {
        return $this->hasMany(EmailEvent::class);
    }

    /**
     * Get the campaign contacts records.
     */
    public function campaignContacts(): HasMany
    {
        return $this->hasMany(CampaignContact::class);
    }
}
