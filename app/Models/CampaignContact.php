<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignContact extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_OPENED = 'opened';

    public const STATUS_CLICKED = 'clicked';

    public const STATUS_RESPONDED = 'responded';

    public const STATUS_BOUNCED = 'bounced';

    public const STATUS_FAILED = 'failed';
    
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'campaign_id',
        'contact_id',
        'status',
        'message_id',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'responded_at',
        'failed_at',
        'unsubscribed_at',
        'failure_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'responded_at' => 'datetime',
        'failed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    /**
     * Get the campaign associated with this record.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the contact associated with this record.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
