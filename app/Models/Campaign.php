<?php

namespace App\Models;

use App\States\CampaignState;
use App\States\CampaignStateFactory;
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

    // Campaign type constants
    public const TYPE_CONTACT = 'contact';

    public const TYPE_COMPANY = 'company';

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
        'type',
    ];

    /**
     * The attributes that should be set to a default value when not present.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => self::TYPE_CONTACT,
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

    /**
     * Get the companies associated with this campaign.
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'campaign_companies')
            ->withTimestamps();
    }

    /**
     * Get the current state of the campaign
     */
    public function getState(): CampaignState
    {
        return CampaignStateFactory::createState($this);
    }

    /**
     * Schedule the campaign for future sending
     *
     * @return bool Whether the operation was successful
     */
    public function schedule(array $data): bool
    {
        return $this->getState()->schedule($this, $data);
    }

    /**
     * Send the campaign immediately
     *
     * @return bool Whether the operation was successful
     */
    public function send(): bool
    {
        return $this->getState()->send($this);
    }

    /**
     * Pause the campaign
     *
     * @return bool Whether the operation was successful
     */
    public function pause(): bool
    {
        return $this->getState()->pause($this);
    }

    /**
     * Resume the campaign
     *
     * @return bool Whether the operation was successful
     */
    public function resume(): bool
    {
        return $this->getState()->resume($this);
    }

    /**
     * Stop/cancel the campaign
     *
     * @return bool Whether the operation was successful
     */
    public function stop(): bool
    {
        return $this->getState()->stop($this);
    }

    /**
     * Add contacts to the campaign
     *
     * @return int Number of contacts added
     */
    public function addContacts(array $contactIds): int
    {
        return $this->getState()->addContacts($this, $contactIds);
    }

    /**
     * Remove contacts from the campaign
     *
     * @return int Number of contacts removed
     */
    public function removeContacts(array $contactIds): int
    {
        return $this->getState()->removeContacts($this, $contactIds);
    }

    /**
     * Check if the campaign can be processed by the job processor
     */
    public function canProcess(): bool
    {
        return $this->getState()->canProcess();
    }

    /**
     * Check if the campaign can transition to the given state
     */
    public function canTransitionTo(string $state): bool
    {
        return $this->getState()->canTransitionTo($state);
    }

    /**
     * Get allowed state transitions for the current state
     */
    public function getAllowedTransitions(): array
    {
        return $this->getState()->getAllowedTransitions();
    }
}
