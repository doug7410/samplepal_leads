import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Campaign } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowUpRight, Calendar, CheckCircle, FilterIcon, Mail, Pause, PenLine, Play, Plus, Send, X } from 'lucide-react';

interface CampaignsIndexProps {
    campaigns: {
        data: Campaign[];
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: {
        status?: string;
    };
}

// Status badge mapping for campaign status
const statusBadge = {
    draft: { label: 'Draft', color: 'bg-gray-100 text-gray-800' },
    scheduled: { label: 'Scheduled', color: 'bg-blue-100 text-blue-800' },
    in_progress: { label: 'In Progress', color: 'bg-yellow-100 text-yellow-800' },
    completed: { label: 'Completed', color: 'bg-green-100 text-green-800' },
    paused: { label: 'Paused', color: 'bg-red-100 text-red-800' },
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Campaigns',
        href: '/campaigns',
    },
];

export default function CampaignsIndex({ campaigns, filters }: CampaignsIndexProps) {
    // Handle status filter change
    const handleStatusChange = (value: string) => {
        router.get(
            route('campaigns.index'),
            {
                status: value === 'all' ? null : value,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Email Campaigns" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <h1 className="text-2xl font-bold">Email Campaigns</h1>
                        {filters && filters.status && (
                            <div className="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-800/30 dark:text-blue-400">
                                Filtered
                            </div>
                        )}
                    </div>
                    <Button asChild>
                        <Link href={route('campaigns.create')} className="flex items-center gap-1">
                            <Plus size={16} />
                            <span>New Campaign</span>
                        </Link>
                    </Button>
                </div>

                {/* Filters */}
                <Card className="p-4">
                    <div className="flex flex-col space-y-4">
                        <div className="flex items-center gap-2">
                            <FilterIcon size={16} className="text-neutral-500" />
                            <h2 className="text-sm font-medium">Filters</h2>
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div className="space-y-2">
                                <label htmlFor="status-filter" className="text-sm font-medium">
                                    Status
                                </label>
                                <Select value={(filters && filters.status) || 'all'} onValueChange={handleStatusChange}>
                                    <SelectTrigger id="status-filter" className="w-full">
                                        <SelectValue placeholder="Filter by status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Statuses</SelectItem>
                                        <SelectItem value="draft">Draft</SelectItem>
                                        <SelectItem value="scheduled">Scheduled</SelectItem>
                                        <SelectItem value="in_progress">In Progress</SelectItem>
                                        <SelectItem value="completed">Completed</SelectItem>
                                        <SelectItem value="paused">Paused</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Reset filters button - only show when filters are active */}
                            {filters && filters.status && (
                                <div className="flex items-end">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="flex items-center gap-1"
                                        onClick={() => router.get(route('campaigns.index'))}
                                    >
                                        <X size={14} />
                                        <span>Reset Filters</span>
                                    </Button>
                                </div>
                            )}
                        </div>
                    </div>
                </Card>

                {/* Campaigns List */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {campaigns.data.map((campaign) => (
                        <Card key={campaign.id} className="flex flex-col overflow-hidden">
                            <div className="flex-1 p-4">
                                {/* Status Badge */}
                                <div className="mb-2 flex items-start justify-between">
                                    <Badge
                                        className={
                                            campaign.status && statusBadge[campaign.status]
                                                ? statusBadge[campaign.status].color
                                                : 'bg-gray-100 text-gray-800'
                                        }
                                    >
                                        {campaign.status && statusBadge[campaign.status] ? statusBadge[campaign.status].label : 'Unknown'}
                                    </Badge>

                                    {/* Campaign Created Time */}
                                    <span className="text-xs text-gray-500">Created {new Date(campaign.created_at).toLocaleDateString()}</span>
                                </div>

                                {/* Campaign Name */}
                                <h3 className="mb-1 text-lg font-semibold">{campaign.name}</h3>

                                {/* Campaign Subject */}
                                <p className="mb-3 truncate text-sm text-gray-500">
                                    <span className="font-medium">Subject:</span> {campaign.subject}
                                </p>

                                {/* Campaign Description */}
                                {campaign.description && <p className="mb-4 line-clamp-2 text-sm text-gray-700">{campaign.description}</p>}

                                {/* Campaign Details */}
                                <div className="space-y-2 text-sm">
                                    {/* From */}
                                    <div className="flex items-center gap-1">
                                        <Mail size={14} className="text-gray-500" />
                                        <span className="text-gray-700">
                                            From: {campaign.from_name ? `${campaign.from_name} <${campaign.from_email}>` : campaign.from_email}
                                        </span>
                                    </div>

                                    {/* Scheduled Time */}
                                    {campaign.scheduled_at && (
                                        <div className="flex items-center gap-1">
                                            <Calendar size={14} className="text-gray-500" />
                                            <span className="text-gray-700">Scheduled: {new Date(campaign.scheduled_at).toLocaleString()}</span>
                                        </div>
                                    )}

                                    {/* Completed Time */}
                                    {campaign.completed_at && (
                                        <div className="flex items-center gap-1">
                                            <CheckCircle size={14} className="text-gray-500" />
                                            <span className="text-gray-700">Completed: {new Date(campaign.completed_at).toLocaleString()}</span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Campaign Actions */}
                            <div className="flex justify-between border-t border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                                <div className="flex gap-2">
                                    {/* View Button */}
                                    <Button size="sm" variant="ghost" asChild>
                                        <Link href={route('campaigns.show', { campaign: campaign.id })} className="flex items-center gap-1">
                                            <ArrowUpRight size={14} />
                                            <span>View</span>
                                        </Link>
                                    </Button>

                                    {/* Edit Button - only for draft campaigns */}
                                    {campaign.status && campaign.status === 'draft' && (
                                        <Button size="sm" variant="ghost" asChild>
                                            <Link href={route('campaigns.edit', { campaign: campaign.id })} className="flex items-center gap-1">
                                                <PenLine size={14} />
                                                <span>Edit</span>
                                            </Link>
                                        </Button>
                                    )}
                                </div>

                                <div className="flex gap-2">
                                    {/* Campaign Controls based on status */}
                                    {campaign.status && campaign.status === 'draft' && (
                                        <Button
                                            size="sm"
                                            onClick={() => router.post(route('campaigns.send', { campaign: campaign.id }))}
                                            className="flex items-center gap-1"
                                        >
                                            <Send size={14} />
                                            <span>Send</span>
                                        </Button>
                                    )}

                                    {campaign.status && campaign.status === 'scheduled' && (
                                        <Button
                                            size="sm"
                                            onClick={() => router.post(route('campaigns.send', { campaign: campaign.id }))}
                                            className="flex items-center gap-1"
                                        >
                                            <Send size={14} />
                                            <span>Send Now</span>
                                        </Button>
                                    )}

                                    {campaign.status && campaign.status === 'in_progress' && (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => router.post(route('campaigns.pause', { campaign: campaign.id }))}
                                            className="flex items-center gap-1"
                                        >
                                            <Pause size={14} />
                                            <span>Pause</span>
                                        </Button>
                                    )}

                                    {campaign.status && campaign.status === 'paused' && (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => router.post(route('campaigns.resume', { campaign: campaign.id }))}
                                            className="flex items-center gap-1"
                                        >
                                            <Play size={14} />
                                            <span>Resume</span>
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </Card>
                    ))}

                    {/* Empty State */}
                    {campaigns.data.length === 0 && (
                        <Card className="col-span-full p-8 text-center">
                            <Mail size={48} className="mx-auto mb-4 text-gray-300" />
                            <h3 className="mb-2 text-lg font-medium">No campaigns found</h3>
                            <p className="mb-4 text-gray-500">
                                {filters && filters.status
                                    ? `No campaigns with status "${filters.status}" found.`
                                    : 'Get started by creating your first email campaign.'}
                            </p>
                            <Button asChild>
                                <Link href={route('campaigns.create')}>Create Campaign</Link>
                            </Button>
                        </Card>
                    )}
                </div>

                {/* Pagination will go here if needed */}
            </div>
        </AppLayout>
    );
}
