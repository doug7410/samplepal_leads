import { ColumnToggle } from '@/components/column-toggle';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useColumnVisibility } from '@/hooks/use-column-visibility';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Campaign, type CampaignContact, type CampaignSegment, type SegmentStatistics } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Building,
    Calendar,
    ChevronLeft,
    Edit,
    Filter,
    MoreHorizontal,
    Pause,
    Play,
    Scissors,
    Send,
    Trash2,
    User,
    Users,
    XCircle,
} from 'lucide-react';
import { useMemo, useState } from 'react';

interface CampaignStatistics {
    total: number;
    statuses: Record<string, number>;
    rates: {
        delivery: number;
        click: number;
    };
}

interface CampaignShowProps {
    campaign: Campaign & {
        campaign_contacts: CampaignContact[];
    };
    statistics: CampaignStatistics;
    segmentStatistics?: Record<number, SegmentStatistics>;
}

const statusBadge: Record<string, { label: string; color: string }> = {
    draft: { label: 'Draft', color: 'bg-gray-100 text-gray-800' },
    scheduled: { label: 'Scheduled', color: 'bg-blue-100 text-blue-800' },
    in_progress: { label: 'In Progress', color: 'bg-yellow-100 text-yellow-800' },
    completed: { label: 'Completed', color: 'bg-green-100 text-green-800' },
    paused: { label: 'Paused', color: 'bg-red-100 text-red-800' },
    failed: { label: 'Failed', color: 'bg-orange-100 text-orange-800' },
};

const contactStatusColors: Record<string, { bg: string; text: string }> = {
    pending: { bg: 'bg-gray-100', text: 'text-gray-800' },
    processing: { bg: 'bg-yellow-100', text: 'text-yellow-800' },
    sent: { bg: 'bg-blue-100', text: 'text-blue-800' },
    delivered: { bg: 'bg-green-100', text: 'text-green-800' },
    clicked: { bg: 'bg-indigo-100', text: 'text-indigo-800' },
    bounced: { bg: 'bg-red-100', text: 'text-red-800' },
    failed: { bg: 'bg-orange-100', text: 'text-orange-800' },
    demo_scheduled: { bg: 'bg-purple-100', text: 'text-purple-800' },
};

export default function CampaignShow({ campaign, statistics, segmentStatistics }: CampaignShowProps) {
    const [scheduleDialogOpen, setScheduleDialogOpen] = useState(false);
    const [segmentDialogOpen, setSegmentDialogOpen] = useState(false);
    const [numberOfSegments, setNumberOfSegments] = useState(2);
    const [editSegment, setEditSegment] = useState<CampaignSegment | null>(null);
    const [editSegmentData, setEditSegmentData] = useState({ name: '', subject: '', content: '' });
    const [scheduledDate, setScheduledDate] = useState<string>(() => {
        const date = new Date();
        date.setHours(date.getHours() + 1);
        return date.toISOString().slice(0, 16);
    });

    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [sortKey, setSortKey] = useState<string | null>(null);
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');
    const [filterSegmentId, setFilterSegmentId] = useState<number | null>(null);

    const hasSegments = campaign.segments && campaign.segments.length > 0;
    const allSegmentsDraft = hasSegments && campaign.segments!.every((s) => s.status === 'draft');
    const segmentsSentCount = hasSegments ? campaign.segments!.filter((s) => s.status === 'completed' || s.status === 'failed').length : 0;

    const toggleableColumns = useMemo(
        () => [
            { key: 'name', label: 'Name' },
            { key: 'email', label: 'Email' },
            { key: 'website', label: 'Website', defaultVisible: false },
            { key: 'company', label: 'Company' },
            { key: 'job_title', label: 'Job Title' },
            { key: 'category', label: 'Category', defaultVisible: false },
            ...(hasSegments ? [{ key: 'segment', label: 'Segment' }] : []),
            { key: 'status', label: 'Status' },
            { key: 'sent', label: 'Sent' },
            { key: 'clicked', label: 'Clicked', defaultVisible: false },
        ],
        [hasSegments],
    );

    const {
        visibleKeys,
        isVisible,
        toggle,
        columns: columnDefs,
    } = useColumnVisibility({
        storageKey: 'campaign-show-columns',
        columns: toggleableColumns,
    });

    const visibleColumnCount = useMemo(() => columnDefs.filter((c) => isVisible(c.key)).length, [columnDefs, isVisible]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Campaigns', href: '/campaigns' },
        { title: campaign.name, href: route('campaigns.show', { campaign: campaign.id }) },
    ];

    function openEditSegment(segment: CampaignSegment) {
        setEditSegment(segment);
        setEditSegmentData({
            name: segment.name,
            subject: segment.subject || '',
            content: segment.content || '',
        });
    }

    function saveSegment() {
        if (!editSegment) return;
        router.put(
            route('campaigns.segments.update', { campaign: campaign.id, segment: editSegment.id }),
            {
                name: editSegmentData.name,
                subject: editSegmentData.subject || null,
                content: editSegmentData.content || null,
            },
            { onSuccess: () => setEditSegment(null) },
        );
    }

    function getSegmentName(segmentId: number | null): string {
        if (!segmentId || !campaign.segments) return '-';
        const seg = campaign.segments.find((s) => s.id === segmentId);
        return seg?.name || '-';
    }

    function toggleSort(key: string) {
        if (sortKey === key) {
            setSortDir(sortDir === 'asc' ? 'desc' : 'asc');
        } else {
            setSortKey(key);
            setSortDir('asc');
        }
    }

    function SortIcon({ columnKey }: { columnKey: string }) {
        if (sortKey !== columnKey) return <ArrowUpDown size={14} className="text-neutral-300" />;
        return sortDir === 'asc' ? <ArrowUp size={14} /> : <ArrowDown size={14} />;
    }

    const sortedContacts = useMemo(() => {
        let filtered = campaign.campaign_contacts.filter((cc) => cc.contact);
        if (filterSegmentId !== null) {
            filtered = filtered.filter((cc) => cc.campaign_segment_id === filterSegmentId);
        }
        if (!sortKey) return filtered;

        return [...filtered].sort((a, b) => {
            let aVal: string | number | null = null;
            let bVal: string | number | null = null;

            switch (sortKey) {
                case 'name':
                    aVal = `${a.contact.first_name} ${a.contact.last_name}`.toLowerCase();
                    bVal = `${b.contact.first_name} ${b.contact.last_name}`.toLowerCase();
                    break;
                case 'email':
                    aVal = (a.contact.email || '').toLowerCase();
                    bVal = (b.contact.email || '').toLowerCase();
                    break;
                case 'company':
                    aVal = (a.contact.company?.company_name || '').toLowerCase();
                    bVal = (b.contact.company?.company_name || '').toLowerCase();
                    break;
                case 'website':
                    aVal = (a.contact.company?.website || '').toLowerCase();
                    bVal = (b.contact.company?.website || '').toLowerCase();
                    break;
                case 'job_title':
                    aVal = (a.contact.job_title || '').toLowerCase();
                    bVal = (b.contact.job_title || '').toLowerCase();
                    break;
                case 'category':
                    aVal = (a.contact.job_title_category || '').toLowerCase();
                    bVal = (b.contact.job_title_category || '').toLowerCase();
                    break;
                case 'segment':
                    aVal = getSegmentName(a.campaign_segment_id).toLowerCase();
                    bVal = getSegmentName(b.campaign_segment_id).toLowerCase();
                    break;
                case 'status':
                    aVal = a.status;
                    bVal = b.status;
                    break;
                case 'sent':
                    aVal = a.sent_at || '';
                    bVal = b.sent_at || '';
                    break;
                case 'clicked':
                    aVal = a.clicked_at || '';
                    bVal = b.clicked_at || '';
                    break;
            }

            if (aVal === null || bVal === null) return 0;
            if (aVal < bVal) return sortDir === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortDir === 'asc' ? 1 : -1;
            return 0;
        });
    }, [campaign.campaign_contacts, sortKey, sortDir, filterSegmentId]);

    const pendingContactIds = useMemo(() => sortedContacts.filter((cc) => cc.status === 'pending').map((cc) => cc.contact_id), [sortedContacts]);

    const allPendingSelected = pendingContactIds.length > 0 && pendingContactIds.every((id) => selectedIds.includes(id));

    function toggleSelectAll() {
        if (allPendingSelected) {
            setSelectedIds([]);
        } else {
            setSelectedIds(pendingContactIds);
        }
    }

    function toggleSelect(contactId: number) {
        setSelectedIds((prev) => (prev.includes(contactId) ? prev.filter((id) => id !== contactId) : [...prev, contactId]));
    }

    function removeContacts(contactIds: number[]) {
        router.post(
            route('campaigns.remove-contacts', { campaign: campaign.id }),
            { contact_ids: contactIds },
            { onSuccess: () => setSelectedIds((prev) => prev.filter((id) => !contactIds.includes(id))) },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Campaign: ${campaign.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild className="mr-2">
                            <Link href={route('campaigns.index')}>
                                <ChevronLeft size={16} />
                                <span>Back to Campaigns</span>
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-bold">{campaign.name}</h1>
                        <Badge className={statusBadge[campaign.status]?.color || 'bg-gray-100 text-gray-800'}>
                            {statusBadge[campaign.status]?.label || campaign.status}
                        </Badge>
                        {hasSegments && (
                            <span className="text-sm text-gray-500">
                                ({segmentsSentCount} of {campaign.segments!.length} segments sent)
                            </span>
                        )}
                    </div>

                    <div className="flex gap-2">
                        {campaign.status === 'draft' && !hasSegments && (
                            <>
                                <Button size="sm" variant="outline" asChild>
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
                                            disabled={
                                                campaign.type === 'contact'
                                                    ? statistics.total === 0
                                                    : !campaign.companies || campaign.companies.length === 0
                                            }
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
                                            <Button variant="outline" onClick={() => setScheduleDialogOpen(false)}>
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
                                    onClick={() => {
                                        if (confirm('Are you sure you want to send this campaign now? This will begin sending emails immediately.')) {
                                            router.post(route('campaigns.send', { campaign: campaign.id }));
                                        }
                                    }}
                                    className="flex items-center gap-1"
                                    disabled={
                                        campaign.type === 'contact' ? statistics.total === 0 : !campaign.companies || campaign.companies.length === 0
                                    }
                                >
                                    <Send size={14} />
                                    <span>Send Now</span>
                                </Button>
                            </>
                        )}

                        {campaign.status === 'draft' && hasSegments && (
                            <Button size="sm" variant="outline" asChild>
                                <Link href={route('campaigns.edit', { campaign: campaign.id })} className="flex items-center gap-1">
                                    <Edit size={14} />
                                    <span>Edit</span>
                                </Link>
                            </Button>
                        )}

                        {campaign.status === 'scheduled' && (
                            <>
                                <Button
                                    size="sm"
                                    onClick={() => {
                                        if (confirm('Are you sure you want to send this campaign now? This will begin sending emails immediately.')) {
                                            router.post(route('campaigns.send', { campaign: campaign.id }));
                                        }
                                    }}
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
                                    className="flex items-center gap-1 border-red-600 text-red-600 hover:bg-red-50"
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
                                        if (
                                            confirm(
                                                'Are you sure you want to stop this campaign and reset it to draft status? This will reset any failed emails so you can try again.',
                                            )
                                        ) {
                                            router.post(route('campaigns.stop', { campaign: campaign.id }));
                                        }
                                    }}
                                    className="flex items-center gap-1 border-red-600 text-red-600 hover:bg-red-50"
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
                                        if (
                                            confirm(
                                                'Are you sure you want to stop this campaign and reset it to draft status? This will reset any failed emails so you can try again.',
                                            )
                                        ) {
                                            router.post(route('campaigns.stop', { campaign: campaign.id }));
                                        }
                                    }}
                                    className="flex items-center gap-1 border-red-600 text-red-600 hover:bg-red-50"
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
                                    if (
                                        confirm(
                                            'Are you sure you want to reset this campaign to draft status? This will allow you to fix any issues and try again.',
                                        )
                                    ) {
                                        router.post(route('campaigns.stop', { campaign: campaign.id }));
                                    }
                                }}
                                className="flex items-center gap-1 border-red-600 text-red-600 hover:bg-red-50"
                            >
                                <XCircle size={14} />
                                <span>Reset to Draft</span>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Campaign details and statistics */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Card className="p-5 md:col-span-2">
                        <h2 className="mb-4 text-lg font-semibold">Campaign Details</h2>

                        <div className="mb-6 grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2">
                            <div>
                                <h3 className="text-sm font-medium text-gray-500">Campaign Type</h3>
                                <p className="flex items-center gap-1 text-base">
                                    {campaign.type === 'company' ? (
                                        <>
                                            <Building size={16} className="text-gray-500" />
                                            <span>Company Campaign</span>
                                        </>
                                    ) : (
                                        <>
                                            <User size={16} className="text-gray-500" />
                                            <span>Individual Contacts</span>
                                        </>
                                    )}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm font-medium text-gray-500">Subject Line</h3>
                                <p className="text-base">{campaign.subject}</p>
                            </div>
                            <div>
                                <h3 className="text-sm font-medium text-gray-500">From</h3>
                                <p className="text-base">
                                    {campaign.from_name ? `${campaign.from_name} <${campaign.from_email}>` : campaign.from_email}
                                </p>
                            </div>
                            <div>
                                <h3 className="text-sm font-medium text-gray-500">Reply-To</h3>
                                <p className="text-base">{campaign.reply_to || campaign.from_email}</p>
                            </div>
                            <div>
                                <h3 className="text-sm font-medium text-gray-500">Created</h3>
                                <p className="text-base">{new Date(campaign.created_at).toLocaleString()}</p>
                            </div>
                            {campaign.scheduled_at && (
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500">Scheduled For</h3>
                                    <p className="text-base">{new Date(campaign.scheduled_at).toLocaleString()}</p>
                                </div>
                            )}
                            {campaign.completed_at && (
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500">Completed</h3>
                                    <p className="text-base">{new Date(campaign.completed_at).toLocaleString()}</p>
                                </div>
                            )}
                        </div>

                        {campaign.description && (
                            <div className="mb-6">
                                <h3 className="mb-2 text-sm font-medium text-gray-500">Description</h3>
                                <p className="text-base">{campaign.description}</p>
                            </div>
                        )}

                        <div>
                            <h3 className="mb-2 text-sm font-medium text-gray-500">Email Content Preview</h3>
                            <div className="rounded-md border bg-white shadow-sm">
                                <div className="border-b bg-gray-50 px-4 py-2 text-xs text-gray-600">
                                    <div className="flex items-center justify-between">
                                        <span>To: {'{recipient_email}'}</span>
                                        <span>
                                            From: {campaign.from_name ? `${campaign.from_name} <${campaign.from_email}>` : campaign.from_email}
                                        </span>
                                    </div>
                                    <div className="mt-1">Subject: {campaign.subject}</div>
                                </div>
                                <div className="max-h-[600px] overflow-auto p-6">
                                    <div
                                        className="email-content"
                                        dangerouslySetInnerHTML={{ __html: campaign.content }}
                                        style={{ fontSize: '14px', lineHeight: '1.6', color: '#333' }}
                                    />
                                </div>
                            </div>
                        </div>
                    </Card>

                    <Card className="p-5 md:col-span-1">
                        <h2 className="mb-4 text-lg font-semibold">Campaign Statistics</h2>

                        <div className="mb-6">
                            <h3 className="mb-2 text-sm font-medium text-gray-500">Recipients</h3>
                            <div className="flex items-center gap-2">
                                <Users size={20} className="text-gray-500" />
                                {campaign.type === 'company' && statistics.total === 0 && campaign.companies && campaign.companies.length > 0 ? (
                                    <div>
                                        <span className="text-xl font-semibold">0</span>
                                        <div className="mt-1 text-xs text-amber-600">Recipients will be processed when you send the campaign</div>
                                        <div className="mt-1 text-xs text-gray-500">
                                            {campaign.companies.length} {campaign.companies.length === 1 ? 'company' : 'companies'} selected
                                        </div>
                                    </div>
                                ) : (
                                    <span className="text-xl font-semibold">{statistics.total}</span>
                                )}
                            </div>
                        </div>

                        <div className="mb-6 space-y-2">
                            <h3 className="text-sm font-medium text-gray-500">Performance</h3>
                            <div className="grid grid-cols-2 gap-2">
                                <div className="rounded-md border bg-white p-3">
                                    <div className="mb-1 text-xs text-gray-500">Delivery Rate</div>
                                    <div className="text-lg font-semibold">{statistics.rates.delivery}%</div>
                                </div>
                                <div className="rounded-md border bg-white p-3">
                                    <div className="mb-1 text-xs text-gray-500">Click Rate</div>
                                    <div className="text-lg font-semibold">{statistics.rates.click}%</div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 className="mb-2 text-sm font-medium text-gray-500">Status Breakdown</h3>
                            <div className="space-y-2">
                                {Object.entries(statistics.statuses)
                                    .filter(([status]) => status !== 'opened' && status !== 'responded')
                                    .map(([status, count]) => (
                                        <div key={status} className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <div className={`h-3 w-3 rounded-full ${contactStatusColors[status]?.bg || 'bg-gray-100'}`}></div>
                                                <span className="capitalize">{status === 'demo_scheduled' ? 'Demo Scheduled' : status}</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium">{count}</span>
                                                <span className="text-xs text-gray-500">
                                                    ({statistics.total > 0 ? ((count / statistics.total) * 100).toFixed(1) : 0}%)
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Segments Section */}
                {campaign.type === 'contact' && (
                    <Card className="p-5">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Segments</h2>
                            <div className="flex gap-2">
                                {campaign.status === 'draft' && !hasSegments && statistics.total > 0 && (
                                    <Dialog open={segmentDialogOpen} onOpenChange={setSegmentDialogOpen}>
                                        <DialogTrigger asChild>
                                            <Button size="sm" variant="outline" className="flex items-center gap-1">
                                                <Scissors size={14} />
                                                <span>Split into Segments</span>
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Split into Segments</DialogTitle>
                                                <DialogDescription>
                                                    Split {statistics.total} contacts into segments. Each segment can be sent independently with
                                                    optional email modifications.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <div className="py-4">
                                                <Label htmlFor="num-segments">Number of Segments</Label>
                                                <Input
                                                    id="num-segments"
                                                    type="number"
                                                    min={2}
                                                    max={20}
                                                    value={numberOfSegments}
                                                    onChange={(e) => setNumberOfSegments(parseInt(e.target.value) || 2)}
                                                    className="mt-1"
                                                />
                                                <p className="mt-2 text-sm text-gray-500">
                                                    ~{Math.ceil(statistics.total / numberOfSegments)} contacts per segment
                                                </p>
                                            </div>
                                            <DialogFooter>
                                                <Button variant="outline" onClick={() => setSegmentDialogOpen(false)}>
                                                    Cancel
                                                </Button>
                                                <Button
                                                    onClick={() => {
                                                        router.post(route('campaigns.segments.store', { campaign: campaign.id }), {
                                                            number_of_segments: numberOfSegments,
                                                        });
                                                        setSegmentDialogOpen(false);
                                                    }}
                                                >
                                                    Create Segments
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                )}

                                {hasSegments && allSegmentsDraft && (
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => {
                                            if (confirm('Remove all segments? Contacts will remain in the campaign.')) {
                                                router.delete(route('campaigns.segments.destroy', { campaign: campaign.id }));
                                            }
                                        }}
                                        className="flex items-center gap-1 border-red-600 text-red-600 hover:bg-red-50"
                                    >
                                        <Trash2 size={14} />
                                        <span>Remove Segments</span>
                                    </Button>
                                )}
                            </div>
                        </div>

                        {!hasSegments && (
                            <p className="text-sm text-gray-500">
                                No segments created. Split the campaign into segments to send to groups of contacts one at a time.
                            </p>
                        )}

                        {hasSegments && (
                            <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                                {campaign.segments!.map((segment) => {
                                    const segStats = segmentStatistics?.[segment.id];
                                    const hasOverride = segment.subject !== null || segment.content !== null;
                                    return (
                                        <div key={segment.id} className="rounded-lg border p-4">
                                            <div className="mb-3 flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium">{segment.name}</span>
                                                    <Badge className={statusBadge[segment.status]?.color || 'bg-gray-100 text-gray-800'}>
                                                        {statusBadge[segment.status]?.label || segment.status}
                                                    </Badge>
                                                </div>
                                            </div>

                                            <div className="mb-3 space-y-1 text-sm text-gray-600">
                                                <div className="flex justify-between">
                                                    <span>Recipients</span>
                                                    <span className="font-medium">{segStats?.total ?? 0}</span>
                                                </div>
                                                {segment.status !== 'draft' && segStats && (
                                                    <>
                                                        <div className="flex justify-between">
                                                            <span>Delivery</span>
                                                            <span className="font-medium">{segStats.rates.delivery}%</span>
                                                        </div>
                                                        <div className="flex justify-between">
                                                            <span>Clicks</span>
                                                            <span className="font-medium">{segStats.rates.click}%</span>
                                                        </div>
                                                    </>
                                                )}
                                            </div>

                                            <div className="mb-3 text-xs text-gray-500">
                                                {hasOverride ? (
                                                    <span className="text-blue-600">Custom subject/content</span>
                                                ) : (
                                                    <span>Using campaign defaults</span>
                                                )}
                                            </div>

                                            <div className="flex gap-2">
                                                <Button
                                                    size="sm"
                                                    variant={filterSegmentId === segment.id ? 'default' : 'outline'}
                                                    onClick={() => setFilterSegmentId(filterSegmentId === segment.id ? null : segment.id)}
                                                >
                                                    <Filter size={12} />
                                                    <span className="ml-1">{filterSegmentId === segment.id ? 'Filtered' : 'Filter'}</span>
                                                </Button>
                                                {segment.status === 'draft' && (
                                                    <>
                                                        <Button size="sm" variant="outline" onClick={() => openEditSegment(segment)}>
                                                            <Edit size={12} />
                                                            <span className="ml-1">Edit</span>
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            onClick={() => {
                                                                if (
                                                                    confirm(
                                                                        'Are you sure you want to send this segment now? This will begin sending emails immediately.',
                                                                    )
                                                                ) {
                                                                    router.post(
                                                                        route('campaigns.segments.send', {
                                                                            campaign: campaign.id,
                                                                            segment: segment.id,
                                                                        }),
                                                                    );
                                                                }
                                                            }}
                                                        >
                                                            <Send size={12} />
                                                            <span className="ml-1">Send</span>
                                                        </Button>
                                                    </>
                                                )}
                                                {segment.status === 'in_progress' && <span className="text-sm text-yellow-600">Sending...</span>}
                                                {segment.sent_at && (
                                                    <span className="text-xs text-gray-400">
                                                        Sent {new Date(segment.sent_at).toLocaleDateString()}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </Card>
                )}

                {/* Segment Edit Dialog */}
                <Dialog open={editSegment !== null} onOpenChange={(open) => !open && setEditSegment(null)}>
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Edit {editSegment?.name}</DialogTitle>
                            <DialogDescription>
                                Customize the email for this segment. Leave fields empty to use the campaign defaults.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4 py-4">
                            <div>
                                <Label htmlFor="segment-name">Segment Name</Label>
                                <Input
                                    id="segment-name"
                                    value={editSegmentData.name}
                                    onChange={(e) => setEditSegmentData({ ...editSegmentData, name: e.target.value })}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <Label htmlFor="segment-subject">Subject Line Override</Label>
                                <Input
                                    id="segment-subject"
                                    value={editSegmentData.subject}
                                    onChange={(e) => setEditSegmentData({ ...editSegmentData, subject: e.target.value })}
                                    placeholder={campaign.subject}
                                    className="mt-1"
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    {editSegmentData.subject ? 'Custom subject' : `Using campaign default: "${campaign.subject}"`}
                                </p>
                            </div>
                            <div>
                                <Label htmlFor="segment-content">Content Override</Label>
                                <Textarea
                                    id="segment-content"
                                    value={editSegmentData.content}
                                    onChange={(e) => setEditSegmentData({ ...editSegmentData, content: e.target.value })}
                                    placeholder="Leave empty to use campaign content"
                                    rows={8}
                                    className="mt-1"
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    {editSegmentData.content ? 'Custom content' : 'Using campaign default content'}
                                </p>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setEditSegment(null)}>
                                Cancel
                            </Button>
                            {(editSegmentData.subject || editSegmentData.content) && (
                                <Button variant="outline" onClick={() => setEditSegmentData({ ...editSegmentData, subject: '', content: '' })}>
                                    Clear Overrides
                                </Button>
                            )}
                            <Button onClick={saveSegment}>Save</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Recipients Table */}
                <Card className="p-5">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Campaign Recipients</h2>
                        <div className="flex items-center gap-3">
                            {selectedIds.length > 0 && (
                                <>
                                    <span className="text-sm text-gray-600">{selectedIds.length} selected</span>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => {
                                            if (confirm(`Remove ${selectedIds.length} contact(s) from the campaign?`)) {
                                                removeContacts(selectedIds);
                                            }
                                        }}
                                        className="flex items-center gap-1 border-red-600 text-red-600 hover:bg-red-50"
                                    >
                                        <Trash2 size={14} />
                                        <span>Remove Selected</span>
                                    </Button>
                                </>
                            )}
                            <ColumnToggle columns={columnDefs} visibleKeys={visibleKeys} onToggle={toggle} />
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b bg-neutral-50 text-left text-sm font-medium text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800">
                                    <th className="px-4 py-3">
                                        {pendingContactIds.length > 0 && (
                                            <Checkbox
                                                checked={allPendingSelected}
                                                onCheckedChange={toggleSelectAll}
                                                aria-label="Select all pending"
                                            />
                                        )}
                                    </th>
                                    {columnDefs
                                        .filter((col) => isVisible(col.key))
                                        .map((col) => (
                                            <th
                                                key={col.key}
                                                className="cursor-pointer px-4 py-3 select-none hover:text-neutral-700 dark:hover:text-neutral-300"
                                                onClick={() => toggleSort(col.key)}
                                            >
                                                <span className="inline-flex items-center gap-1">
                                                    {col.label}
                                                    <SortIcon columnKey={col.key} />
                                                </span>
                                            </th>
                                        ))}
                                    <th className="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                {sortedContacts.map((cc) => (
                                    <tr key={cc.id} className="hover:bg-neutral-100 dark:hover:bg-neutral-800">
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">
                                            {cc.status === 'pending' && (
                                                <Checkbox
                                                    checked={selectedIds.includes(cc.contact_id)}
                                                    onCheckedChange={() => toggleSelect(cc.contact_id)}
                                                    aria-label={`Select ${cc.contact.first_name} ${cc.contact.last_name}`}
                                                />
                                            )}
                                        </td>
                                        {isVisible('name') && (
                                            <td className="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                                {cc.contact.first_name} {cc.contact.last_name}
                                            </td>
                                        )}
                                        {isVisible('email') && (
                                            <td className="px-4 py-3 text-sm whitespace-nowrap">
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
                                        )}
                                        {isVisible('website') && (
                                            <td className="px-4 py-3 text-sm whitespace-nowrap">
                                                {cc.contact.company?.website ? (
                                                    <a
                                                        href={
                                                            cc.contact.company.website.startsWith('http')
                                                                ? cc.contact.company.website
                                                                : `https://${cc.contact.company.website}`
                                                        }
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="text-blue-600 hover:underline dark:text-blue-400"
                                                    >
                                                        {cc.contact.company.website.replace(/^https?:\/\//, '')}
                                                    </a>
                                                ) : (
                                                    '-'
                                                )}
                                            </td>
                                        )}
                                        {isVisible('company') && (
                                            <td className="px-4 py-3 text-sm whitespace-nowrap">
                                                <Link
                                                    href={route('companies.index', { search: cc.contact.company?.company_name })}
                                                    className="text-blue-600 hover:underline dark:text-blue-400"
                                                >
                                                    {cc.contact.company?.company_name || '-'}
                                                </Link>
                                            </td>
                                        )}
                                        {isVisible('job_title') && (
                                            <td className="px-4 py-3 text-sm whitespace-nowrap">{cc.contact.job_title || '-'}</td>
                                        )}
                                        {isVisible('category') && (
                                            <td className="px-4 py-3 text-sm whitespace-nowrap">{cc.contact.job_title_category || '-'}</td>
                                        )}
                                        {isVisible('segment') && hasSegments && (
                                            <td className="px-4 py-3 text-sm whitespace-nowrap">{getSegmentName(cc.campaign_segment_id)}</td>
                                        )}
                                        {isVisible('status') && (
                                            <td className="px-4 py-3 text-sm whitespace-nowrap">
                                                <Badge
                                                    className={`${contactStatusColors[cc.status]?.bg || 'bg-gray-100'} ${contactStatusColors[cc.status]?.text || 'text-gray-800'}`}
                                                >
                                                    {cc.status}
                                                </Badge>
                                            </td>
                                        )}
                                        {isVisible('sent') && (
                                            <td className="px-4 py-3 text-sm whitespace-nowrap">
                                                {cc.sent_at ? new Date(cc.sent_at).toLocaleString() : '-'}
                                            </td>
                                        )}
                                        {isVisible('clicked') && (
                                            <td className="px-4 py-3 text-sm whitespace-nowrap">
                                                {cc.clicked_at ? new Date(cc.clicked_at).toLocaleString() : '-'}
                                            </td>
                                        )}
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" className="h-8 w-8 p-0">
                                                        <span className="sr-only">Open menu</span>
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem onClick={() => router.visit(route('contacts.edit', { id: cc.contact.id }))}>
                                                        Edit Contact
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        onClick={() => {
                                                            router.put(
                                                                route('campaigns.contacts.update-status', {
                                                                    campaign: campaign.id,
                                                                    campaignContact: cc.id,
                                                                }),
                                                                { status: 'demo_scheduled' },
                                                            );
                                                        }}
                                                    >
                                                        Mark as Demo Scheduled
                                                    </DropdownMenuItem>
                                                    {cc.status === 'pending' && (
                                                        <DropdownMenuItem
                                                            className="text-red-600 focus:text-red-600"
                                                            onClick={() => {
                                                                if (confirm('Remove this contact from the campaign?')) {
                                                                    removeContacts([cc.contact_id]);
                                                                }
                                                            }}
                                                        >
                                                            Remove from Campaign
                                                        </DropdownMenuItem>
                                                    )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </td>
                                    </tr>
                                ))}

                                {campaign.campaign_contacts.length === 0 && (
                                    <tr>
                                        <td colSpan={visibleColumnCount + 2} className="px-4 py-6 text-center text-neutral-500">
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
