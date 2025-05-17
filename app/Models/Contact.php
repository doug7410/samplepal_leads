<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'email',
        'cell_phone',
        'office_phone',
        'job_title',
        'has_been_contacted',
        'has_unsubscribed',
        'unsubscribed_at',
        'deal_status',
        'notes',
        'relevance_score',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'has_been_contacted' => 'boolean',
        'has_unsubscribed' => 'boolean',
        'unsubscribed_at' => 'datetime',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the company that the contact belongs to.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the campaigns associated with this contact.
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_contacts')
            ->withPivot('status', 'message_id', 'sent_at', 'delivered_at', 'opened_at',
                'clicked_at', 'responded_at', 'failed_at', 'failure_reason')
            ->withTimestamps();
    }

    /**
     * Get the email events for this contact.
     */
    public function emailEvents(): HasMany
    {
        return $this->hasMany(EmailEvent::class);
    }

    /**
     * Get the campaign contacts records for this contact.
     */
    public function campaignContacts(): HasMany
    {
        return $this->hasMany(CampaignContact::class);
    }
}
