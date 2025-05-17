<?php

namespace App\Commands\Campaigns;

use App\Models\CampaignContact;

class StopCampaignCommand extends CampaignCommand
{
    /**
     * Execute the command to stop the campaign
     *
     * @return bool Whether the operation was successful
     */
    public function execute(): bool
    {
        // For drafts, we want to reset all contacts to pending
        if ($this->campaign->status === 'draft') {
            // Reset all contacts to pending
            foreach ($this->campaign->campaignContacts()->get() as $contact) {
                $contact->status = CampaignContact::STATUS_PENDING;
                $contact->message_id = null;
                $contact->sent_at = null;
                $contact->delivered_at = null;
                $contact->opened_at = null;
                $contact->clicked_at = null;
                $contact->responded_at = null;
                $contact->failed_at = null;
                $contact->failure_reason = null;
                $contact->save();
            }
            return true;
        }
        
        // For in-progress, paused, or scheduled campaigns, reset to draft
        if (in_array($this->campaign->status, ['in_progress', 'paused', 'scheduled', 'failed'])) {
            // Reset all contacts to pending
            foreach ($this->campaign->campaignContacts()->get() as $contact) {
                $contact->status = CampaignContact::STATUS_PENDING;
                $contact->message_id = null;
                $contact->sent_at = null;
                $contact->delivered_at = null;
                $contact->opened_at = null;
                $contact->clicked_at = null;
                $contact->responded_at = null;
                $contact->failed_at = null;
                $contact->failure_reason = null;
                $contact->save();
            }
            
            // Reset campaign status to draft
            $this->campaign->status = 'draft';
            $this->campaign->scheduled_at = null;
            $this->campaign->completed_at = null;
            $this->campaign->save();
            
            return true;
        }
        
        // For completed campaigns, leave sent contacts alone but reset status to draft
        if ($this->campaign->status === 'completed') {
            // Mark pending, processing, or failed contacts as pending again
            foreach ($this->campaign->campaignContacts()
                ->whereIn('status', [
                    CampaignContact::STATUS_PENDING,
                    CampaignContact::STATUS_PROCESSING,
                    CampaignContact::STATUS_FAILED,
                    CampaignContact::STATUS_CANCELLED
                ])->get() as $contact) {
                
                $contact->status = CampaignContact::STATUS_PENDING;
                $contact->message_id = null;
                $contact->failed_at = null;
                $contact->failure_reason = null;
                $contact->save();
            }
            
            // Reset campaign status to draft
            $this->campaign->status = 'draft';
            $this->campaign->scheduled_at = null;
            $this->campaign->completed_at = null;
            $this->campaign->save();
            
            return true;
        }

        // Default behavior (should never reach here)
        return $this->campaign->stop();
    }
}
