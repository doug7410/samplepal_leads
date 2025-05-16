<?php

namespace App\Console\Commands;

use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckScheduledCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-scheduled-campaigns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for scheduled campaigns that should be started';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for scheduled campaigns...');
        
        // Find scheduled campaigns that should start now
        $campaigns = Campaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();
            
        if ($campaigns->isEmpty()) {
            $this->info('No campaigns to start at this time.');
            return;
        }
        
        $this->info('Found ' . $campaigns->count() . ' campaigns to start.');
        
        foreach ($campaigns as $campaign) {
            $this->info("Starting campaign #{$campaign->id}: {$campaign->name}");
            
            // Update the campaign status
            $campaign->status = 'in_progress';
            $campaign->save();
            
            // Dispatch the process job
            ProcessCampaignJob::dispatch($campaign);
            
            Log::info("Scheduled campaign #{$campaign->id} started.");
        }
        
        $this->info('Done processing scheduled campaigns.');
    }
}
