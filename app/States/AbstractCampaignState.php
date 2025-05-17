<?php

namespace App\States;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;

abstract class AbstractCampaignState implements CampaignState
{
    /**
     * Default implementations for methods that are often the same across states.
     * Each state will override the methods where behavior differs.
     */

    /**
     * Schedule the campaign for future sending.
     * Default implementation - not allowed, should be overridden by states that support it.
     */
    public function schedule(Campaign $campaign, array $data): bool
    {
        Log::info("Cannot schedule campaign in '{$this->getIdentifier()}' state.");

        return false;
    }

    /**
     * Send the campaign immediately.
     * Default implementation - not allowed, should be overridden by states that support it.
     */
    public function send(Campaign $campaign): bool
    {
        Log::info("Cannot send campaign in '{$this->getIdentifier()}' state.");

        return false;
    }

    /**
     * Pause the campaign.
     * Default implementation - not allowed, should be overridden by states that support it.
     */
    public function pause(Campaign $campaign): bool
    {
        Log::info("Cannot pause campaign in '{$this->getIdentifier()}' state.");

        return false;
    }

    /**
     * Resume the campaign.
     * Default implementation - not allowed, should be overridden by states that support it.
     */
    public function resume(Campaign $campaign): bool
    {
        Log::info("Cannot resume campaign in '{$this->getIdentifier()}' state.");

        return false;
    }

    /**
     * Stop/cancel the campaign.
     * Default implementation - not allowed, should be overridden by states that support it.
     */
    public function stop(Campaign $campaign): bool
    {
        Log::info("Cannot stop campaign in '{$this->getIdentifier()}' state.");

        return false;
    }

    /**
     * The default implementation for adding contacts.
     * Most states allow adding contacts except for completed, failed states.
     */
    public function addContacts(Campaign $campaign, array $contactIds): int
    {
        // Find contacts that aren't already in the campaign
        $existingContactIds = $campaign->campaignContacts()
            ->whereIn('contact_id', $contactIds)
            ->pluck('contact_id')
            ->toArray();

        $newContactIds = array_diff($contactIds, $existingContactIds);

        if (empty($newContactIds)) {
            return 0;
        }

        // Add new contacts
        $contactsAdded = 0;
        foreach ($newContactIds as $contactId) {
            try {
                // Verify the contact exists
                $contact = Contact::find($contactId);
                if (! $contact) {
                    Log::warning("Contact #{$contactId} not found, skipping.");

                    continue;
                }

                // Create campaign contact record
                CampaignContact::create([
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contactId,
                    'status' => CampaignContact::STATUS_PENDING,
                ]);

                $contactsAdded++;
            } catch (\Exception $e) {
                Log::error("Error adding contact #{$contactId} to campaign: ".$e->getMessage());
            }
        }

        Log::info("Added {$contactsAdded} contacts to campaign #{$campaign->id}");

        return $contactsAdded;
    }

    /**
     * The default implementation for removing contacts.
     * Most states allow removing contacts.
     */
    public function removeContacts(Campaign $campaign, array $contactIds): int
    {
        if (empty($contactIds)) {
            return 0;
        }

        // Only remove pending contacts
        $count = $campaign->campaignContacts()
            ->whereIn('contact_id', $contactIds)
            ->where('status', CampaignContact::STATUS_PENDING)
            ->delete();

        Log::info("Removed {$count} contacts from campaign #{$campaign->id}");

        return $count;
    }

    /**
     * Check if the campaign can be processed by the job processor.
     * Default is false - only in_progress campaigns can be processed.
     */
    public function canProcess(): bool
    {
        return false;
    }

    /**
     * Check if the campaign can transition to the given state
     */
    public function canTransitionTo(string $stateIdentifier): bool
    {
        return in_array($stateIdentifier, $this->getAllowedTransitions());
    }
}
