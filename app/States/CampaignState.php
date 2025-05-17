<?php

namespace App\States;

use App\Models\Campaign;

interface CampaignState
{
    /**
     * Get a unique identifier for this state.
     */
    public function getIdentifier(): string;

    /**
     * Get a human-readable name for this state.
     */
    public function getName(): string;

    /**
     * Schedule the campaign for future sending
     *
     * @return bool Whether the operation was successful
     */
    public function schedule(Campaign $campaign, array $data): bool;

    /**
     * Send the campaign immediately
     *
     * @return bool Whether the operation was successful
     */
    public function send(Campaign $campaign): bool;

    /**
     * Pause the campaign
     *
     * @return bool Whether the operation was successful
     */
    public function pause(Campaign $campaign): bool;

    /**
     * Resume the campaign
     *
     * @return bool Whether the operation was successful
     */
    public function resume(Campaign $campaign): bool;

    /**
     * Stop/cancel the campaign
     *
     * @return bool Whether the operation was successful
     */
    public function stop(Campaign $campaign): bool;

    /**
     * Add contacts to the campaign
     *
     * @return int Number of contacts added
     */
    public function addContacts(Campaign $campaign, array $contactIds): int;

    /**
     * Remove contacts from the campaign
     *
     * @return int Number of contacts removed
     */
    public function removeContacts(Campaign $campaign, array $contactIds): int;

    /**
     * Check if the campaign can be processed by the job processor
     */
    public function canProcess(): bool;

    /**
     * Get allowable transitions from this state
     *
     * @return array Array of state identifiers that this state can transition to
     */
    public function getAllowedTransitions(): array;

    /**
     * Check if the campaign can transition to the given state
     */
    public function canTransitionTo(string $stateIdentifier): bool;
}
