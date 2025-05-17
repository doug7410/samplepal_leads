import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { type BreadcrumbItem, type Campaign, type CampaignContact } from '@/types';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { 
  ArrowLeft,
  Calendar,
  CheckCircle,
  Clock,
  Edit,
  Mail,
  Pause,
  Play,
  Send,
  Users,
  XCircle,
  ChevronLeft,
  ChevronRight,
  PieChart,
  BarChart
} from 'lucide-react';
import { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface CampaignStatistics {
  total: number;
  statuses: {
    pending: number;
    sent: number;
    delivered: number;
    opened: number;
    clicked: number;
    responded: number;
    bounced: number;
    failed: number;
  };
  rates: {
    delivery: number;
    open: number;
    click: number;
    response: number;
  };
}

interface CampaignShowProps {
  campaign: Campaign & {
    campaign_contacts: CampaignContact[];
  };
  statistics: CampaignStatistics;
}

// Status badge mapping for campaign status
const statusBadge: Record<string, { label: string, color: string }> = {
  draft: { label: 'Draft', color: 'bg-gray-100 text-gray-800' },
  scheduled: { label: 'Scheduled', color: 'bg-blue-100 text-blue-800' },
  in_progress: { label: 'In Progress', color: 'bg-yellow-100 text-yellow-800' },
  completed: { label: 'Completed', color: 'bg-green-100 text-green-800' },
  paused: { label: 'Paused', color: 'bg-red-100 text-red-800' },
  failed: { label: 'Failed', color: 'bg-orange-100 text-orange-800' },
};

// Status colors for the contact statuses
const contactStatusColors = {
  pending: { bg: 'bg-gray-100', text: 'text-gray-800' },
  processing: { bg: 'bg-yellow-100', text: 'text-yellow-800' },
  sent: { bg: 'bg-blue-100', text: 'text-blue-800' },
  delivered: { bg: 'bg-green-100', text: 'text-green-800' },
  opened: { bg: 'bg-purple-100', text: 'text-purple-800' },
  clicked: { bg: 'bg-indigo-100', text: 'text-indigo-800' },
  responded: { bg: 'bg-teal-100', text: 'text-teal-800' },
  bounced: { bg: 'bg-red-100', text: 'text-red-800' },
  failed: { bg: 'bg-orange-100', text: 'text-orange-800' },
};

export default function CampaignShow({ campaign, statistics }: CampaignShowProps) {
  const [scheduleDialogOpen, setScheduleDialogOpen] = useState(false);
  const [scheduledDate, setScheduledDate] = useState<string>(() => {
    // Default to one hour from now
    const date = new Date();
    date.setHours(date.getHours() + 1);
    return date.toISOString().slice(0, 16); // Format for datetime-local input
  });
  const breadcrumbs: BreadcrumbItem[] = [
    {
      title: 'Dashboard',
      href: '/dashboard',
    },
    {
      title: 'Campaigns',
      href: '/campaigns',
    },
    {
      title: campaign.name,
      href: route('campaigns.show', { campaign: campaign.id }),
    },
  ];

  // Format the content for display - this would typically render HTML but we're keeping it simple
  const formattedContent = campaign.content.split('\\n').map((line, i) => (
    <p key={i} className="mb-2">{line}</p>
  ));

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Campaign: ${campaign.name}`} />
      <div className="flex h-full flex-1 flex-col gap-4 p-4">
        {/* Header with back button and actions */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" asChild className="mr-2">
              <Link href={route('campaigns.index')}>
                <ChevronLeft size={16} />
                <span>Back to Campaigns</span>
              </Link>
            </Button>
            <h1 className="text-2xl font-bold">{campaign.name}</h1>
            <Badge className={statusBadge[campaign.status as keyof typeof statusBadge]?.color || 'bg-gray-100 text-gray-800'}>
              {statusBadge[campaign.status as keyof typeof statusBadge]?.label || campaign.status}
            </Badge>
          </div>
          
          <div className="flex gap-2">
            {/* Campaign Controls based on status */}
            {campaign.status === 'draft' && (
              <>
                <Button
                  size="sm"
                  variant="outline"
                  asChild
                >
                  <Link href={route('campaigns.edit', { campaign: campaign.id })} className="flex items-center gap-1">
                    <Edit size={14} />
                    <span>Edit</span>
                  </Link>
                </Button>
                <Dialog open={scheduleDialogOpen} onOpenChange={setScheduleDialogOpen}>
                  <DialogTrigger asChild>
                    <Button 
                      size="sm"
                      variant="outline"
                      className="flex items-center gap-1"
                      disabled={statistics.total === 0}
                      title={statistics.total === 0 ? "Add contacts before scheduling" : ""}
                    >
                      <Calendar size={14} />
                      <span>Schedule</span>
                    </Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>Schedule Campaign</DialogTitle>
                      <DialogDescription>
                        Choose when to send this campaign. The campaign will be sent automatically at the specified time.
                      </DialogDescription>
                    </DialogHeader>
                    
                    <div className="py-4">
                      <Label htmlFor="scheduled-time">Schedule Date & Time</Label>
                      <Input
                        id="scheduled-time"
                        type="datetime-local"
                        value={scheduledDate}
                        onChange={(e) => setScheduledDate(e.target.value)}
                        min={new Date().toISOString().slice(0, 16)}
                        className="mt-1"
                      />
                    </div>
                    
                    <DialogFooter>
                      <Button
                        variant="outline"
                        onClick={() => setScheduleDialogOpen(false)}
                      >
                        Cancel
                      </Button>
                      <Button
                        onClick={() => {
                          router.post(route('campaigns.schedule', { campaign: campaign.id }), {
                            scheduled_at: new Date(scheduledDate).toISOString(),
                          });
                          setScheduleDialogOpen(false);
                        }}
                      >
                        Schedule Campaign
                      </Button>
                    </DialogFooter>
                  </DialogContent>
                </Dialog>
                <Button 
                  size="sm" 
                  onClick={() => router.post(route('campaigns.send', { campaign: campaign.id }))}
                  className="flex items-center gap-1"
                  disabled={statistics.total === 0}
                  title={statistics.total === 0 ? "Add contacts before sending" : ""}
                >
                  <Send size={14} />
                  <span>Send Now</span>
                </Button>
              </>
            )}
            
            {campaign.status === 'scheduled' && (
              <>
                <Button 
                  size="sm" 
                  onClick={() => router.post(route('campaigns.send', { campaign: campaign.id }))}
                  className="flex items-center gap-1"
                >
                  <Send size={14} />
                  <span>Send Now</span>
                </Button>
                <Button 
                  size="sm" 
                  variant="outline"
                  onClick={() => {
                    if (confirm('Are you sure you want to cancel the scheduled campaign and reset it to draft status?')) {
                      router.post(route('campaigns.stop', { campaign: campaign.id }));
                    }
                  }}
                  className="flex items-center gap-1 text-red-600 border-red-600 hover:bg-red-50"
                >
                  <XCircle size={14} />
                  <span>Cancel Schedule</span>
                </Button>
              </>
            )}
            
            {campaign.status === 'in_progress' && (
              <>
                <Button 
                  size="sm" 
                  variant="outline"
                  onClick={() => router.post(route('campaigns.pause', { campaign: campaign.id }))}
                  className="flex items-center gap-1"
                >
                  <Pause size={14} />
                  <span>Pause</span>
                </Button>
                <Button 
                  size="sm" 
                  variant="outline"
                  onClick={() => {
                    if (confirm('Are you sure you want to stop this campaign and reset it to draft status? This will reset any failed emails so you can try again.')) {
                      router.post(route('campaigns.stop', { campaign: campaign.id }));
                    }
                  }}
                  className="flex items-center gap-1 text-red-600 border-red-600 hover:bg-red-50"
                >
                  <XCircle size={14} />
                  <span>Stop & Reset</span>
                </Button>
              </>
            )}
            
            {campaign.status === 'paused' && (
              <>
                <Button 
                  size="sm" 
                  variant="outline"
                  onClick={() => router.post(route('campaigns.resume', { campaign: campaign.id }))}
                  className="flex items-center gap-1"
                >
                  <Play size={14} />
                  <span>Resume</span>
                </Button>
                <Button 
                  size="sm" 
                  variant="outline"
                  onClick={() => {
                    if (confirm('Are you sure you want to stop this campaign and reset it to draft status? This will reset any failed emails so you can try again.')) {
                      router.post(route('campaigns.stop', { campaign: campaign.id }));
                    }
                  }}
                  className="flex items-center gap-1 text-red-600 border-red-600 hover:bg-red-50"
                >
                  <XCircle size={14} />
                  <span>Stop & Reset</span>
                </Button>
              </>
            )}
            
            {campaign.status === 'failed' && (
              <Button 
                size="sm" 
                variant="outline"
                onClick={() => {
                  if (confirm('Are you sure you want to reset this campaign to draft status? This will allow you to fix any issues and try again.')) {
                    router.post(route('campaigns.stop', { campaign: campaign.id }));
                  }
                }}
                className="flex items-center gap-1 text-red-600 border-red-600 hover:bg-red-50"
              >
                <XCircle size={14} />
                <span>Reset to Draft</span>
              </Button>
            )}
          </div>
        </div>
        
        {/* Campaign details and statistics in a grid layout */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {/* Campaign Details */}
          <Card className="md:col-span-2 p-5">
            <h2 className="text-lg font-semibold mb-4">Campaign Details</h2>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6">
              {/* Subject Line */}
              <div>
                <h3 className="text-sm font-medium text-gray-500">Subject Line</h3>
                <p className="text-base">{campaign.subject}</p>
              </div>
              
              {/* From */}
              <div>
                <h3 className="text-sm font-medium text-gray-500">From</h3>
                <p className="text-base">
                  {campaign.from_name ? `${campaign.from_name} <${campaign.from_email}>` : campaign.from_email}
                </p>
              </div>
              
              {/* Reply-To */}
              <div>
                <h3 className="text-sm font-medium text-gray-500">Reply-To</h3>
                <p className="text-base">{campaign.reply_to || campaign.from_email}</p>
              </div>
              
              {/* Created At */}
              <div>
                <h3 className="text-sm font-medium text-gray-500">Created</h3>
                <p className="text-base">{new Date(campaign.created_at).toLocaleString()}</p>
              </div>
              
              {/* Scheduled At - only if scheduled */}
              {campaign.scheduled_at && (
                <div>
                  <h3 className="text-sm font-medium text-gray-500">Scheduled For</h3>
                  <p className="text-base">{new Date(campaign.scheduled_at).toLocaleString()}</p>
                </div>
              )}
              
              {/* Completed At - only if completed */}
              {campaign.completed_at && (
                <div>
                  <h3 className="text-sm font-medium text-gray-500">Completed</h3>
                  <p className="text-base">{new Date(campaign.completed_at).toLocaleString()}</p>
                </div>
              )}
            </div>
            
            {/* Description */}
            {campaign.description && (
              <div className="mb-6">
                <h3 className="text-sm font-medium text-gray-500 mb-2">Description</h3>
                <p className="text-base">{campaign.description}</p>
              </div>
            )}
            
            {/* Content Preview */}
            <div>
              <h3 className="text-sm font-medium text-gray-500 mb-2">Email Content</h3>
              <div className="border rounded-md p-4 bg-white">
                {formattedContent}
              </div>
            </div>
          </Card>
          
          {/* Campaign Statistics */}
          <Card className="md:col-span-1 p-5">
            <h2 className="text-lg font-semibold mb-4">Campaign Statistics</h2>
            
            {/* Overall Progress */}
            <div className="mb-6">
              <h3 className="text-sm font-medium text-gray-500 mb-2">Recipients</h3>
              <div className="flex items-center gap-2">
                <Users size={20} className="text-gray-500" />
                <span className="text-xl font-semibold">{statistics.total}</span>
              </div>
            </div>
            
            {/* Rates */}
            <div className="mb-6 space-y-2">
              <h3 className="text-sm font-medium text-gray-500">Performance</h3>
              
              <div className="grid grid-cols-2 gap-2">
                {/* Delivery Rate */}
                <div className="border rounded-md p-3 bg-white">
                  <div className="text-gray-500 text-xs mb-1">Delivery Rate</div>
                  <div className="text-lg font-semibold">{statistics.rates.delivery}%</div>
                </div>
                
                {/* Open Rate */}
                <div className="border rounded-md p-3 bg-white">
                  <div className="text-gray-500 text-xs mb-1">Open Rate</div>
                  <div className="text-lg font-semibold">{statistics.rates.open}%</div>
                </div>
                
                {/* Click Rate */}
                <div className="border rounded-md p-3 bg-white">
                  <div className="text-gray-500 text-xs mb-1">Click Rate</div>
                  <div className="text-lg font-semibold">{statistics.rates.click}%</div>
                </div>
                
                {/* Response Rate */}
                <div className="border rounded-md p-3 bg-white">
                  <div className="text-gray-500 text-xs mb-1">Response Rate</div>
                  <div className="text-lg font-semibold">{statistics.rates.response}%</div>
                </div>
              </div>
            </div>
            
            {/* Status Breakdown */}
            <div>
              <h3 className="text-sm font-medium text-gray-500 mb-2">Status Breakdown</h3>
              <div className="space-y-2">
                {Object.entries(statistics.statuses).map(([status, count]) => (
                  <div key={status} className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <div className={`w-3 h-3 rounded-full ${
                        contactStatusColors[status as keyof typeof contactStatusColors]?.bg || 'bg-gray-100'
                      }`}></div>
                      <span className="capitalize">{status}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <span className="font-medium">{count}</span>
                      <span className="text-gray-500 text-xs">
                        ({statistics.total > 0 ? ((count / statistics.total) * 100).toFixed(1) : 0}%)
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </Card>
        </div>
        
        {/* Contact List */}
        <Card className="p-5">
          <h2 className="text-lg font-semibold mb-4">Campaign Recipients</h2>
          
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b bg-neutral-50 text-left text-sm font-medium text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800">
                  <th className="px-4 py-3">Name</th>
                  <th className="px-4 py-3">Email</th>
                  <th className="px-4 py-3">Company</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Sent</th>
                  <th className="px-4 py-3">Opened</th>
                  <th className="px-4 py-3">Clicked</th>
                  <th className="px-4 py-3">Responded</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                {campaign.campaign_contacts.map((cc) => (
                  <tr 
                    key={cc.id} 
                    className="hover:bg-neutral-100 dark:hover:bg-neutral-800"
                  >
                    <td className="whitespace-nowrap px-4 py-3 text-sm font-medium">
                      {cc.contact.first_name} {cc.contact.last_name}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      {cc.contact.email ? (
                        <a 
                          href={`mailto:${cc.contact.email}`}
                          className="text-blue-600 hover:underline dark:text-blue-400"
                        >
                          {cc.contact.email}
                        </a>
                      ) : (
                        '-'
                      )}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      {cc.contact.company?.company_name || '-'}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      <Badge className={`${
                        contactStatusColors[cc.status as keyof typeof contactStatusColors]?.bg || 'bg-gray-100'
                      } ${
                        contactStatusColors[cc.status as keyof typeof contactStatusColors]?.text || 'text-gray-800'
                      }`}>
                        {cc.status}
                      </Badge>
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      {cc.sent_at ? new Date(cc.sent_at).toLocaleString() : '-'}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      {cc.opened_at ? new Date(cc.opened_at).toLocaleString() : '-'}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      {cc.clicked_at ? new Date(cc.clicked_at).toLocaleString() : '-'}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      {cc.responded_at ? new Date(cc.responded_at).toLocaleString() : '-'}
                    </td>
                  </tr>
                ))}
                
                {campaign.campaign_contacts.length === 0 && (
                  <tr>
                    <td colSpan={8} className="px-4 py-6 text-center text-neutral-500">
                      No recipients added to this campaign yet
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </Card>
      </div>
    </AppLayout>
  );
}