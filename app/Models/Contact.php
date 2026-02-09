<?php

namespace App\Models;

use App\Enums\DealStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

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
        'email_source',
        'cell_phone',
        'office_phone',
        'job_title',
        'job_title_category',
        'has_been_contacted',
        'has_unsubscribed',
        'unsubscribed_at',
        'deal_status',
        'notes',
        'relevance_score',
        'is_enrichment_unusable',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_been_contacted' => 'boolean',
            'has_unsubscribed' => 'boolean',
            'unsubscribed_at' => 'datetime',
            'is_enrichment_unusable' => 'boolean',
            'deal_status' => DealStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Contact>  $query
     */
    public function scopeUsable(Builder $query): void
    {
        $query->where('is_enrichment_unusable', false);
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

    /**
     * Get the sequence contacts records for this contact.
     */
    public function sequenceContacts(): HasMany
    {
        return $this->hasMany(SequenceContact::class);
    }
}
