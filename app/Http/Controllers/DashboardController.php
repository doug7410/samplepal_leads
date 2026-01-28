<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        // Campaign stats
        $activeCampaigns = Campaign::where('user_id', $userId)
            ->whereIn('status', [Campaign::STATUS_IN_PROGRESS, Campaign::STATUS_SCHEDULED])
            ->count();

        $totalCampaigns = Campaign::where('user_id', $userId)->count();

        // Recent campaign performance (last 30 days)
        $recentCampaignIds = Campaign::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(30))
            ->pluck('id');

        $recentStats = CampaignContact::whereIn('campaign_id', $recentCampaignIds)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalSent = ($recentStats['sent'] ?? 0) +
            ($recentStats['delivered'] ?? 0) +
            ($recentStats['opened'] ?? 0) +
            ($recentStats['clicked'] ?? 0) +
            ($recentStats['responded'] ?? 0);

        $totalOpened = ($recentStats['opened'] ?? 0) +
            ($recentStats['clicked'] ?? 0) +
            ($recentStats['responded'] ?? 0);

        $openRate = $totalSent > 0 ? round(($totalOpened / $totalSent) * 100, 1) : 0;

        // Contact pipeline breakdown
        $pipelineBreakdown = Contact::whereHas('company', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->select('deal_status', DB::raw('count(*) as count'))
            ->groupBy('deal_status')
            ->pluck('count', 'deal_status')
            ->toArray();

        // Contacts needing follow-up (contacted but not responded/in_progress/closed)
        $needsFollowUp = Contact::whereHas('company', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->where('deal_status', 'contacted')
            ->where('has_unsubscribed', false)
            ->count();

        // Leads available (none status, not unsubscribed, has email)
        $availableLeads = Contact::whereHas('company', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->where('deal_status', 'none')
            ->where('has_unsubscribed', false)
            ->whereNotNull('email')
            ->count();

        return Inertia::render('dashboard', [
            'stats' => [
                'activeCampaigns' => $activeCampaigns,
                'totalCampaigns' => $totalCampaigns,
                'openRate' => $openRate,
                'totalSent' => $totalSent,
            ],
            'pipeline' => $pipelineBreakdown,
            'needsFollowUp' => $needsFollowUp,
            'availableLeads' => $availableLeads,
        ]);
    }
}
