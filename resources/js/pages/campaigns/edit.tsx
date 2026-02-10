import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import WysiwygEditor from '@/components/wysiwyg-editor';
import { DEAL_STATUSES } from '@/constants/deal-status';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Campaign, type Company, type Contact } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Building, Calendar, ChevronLeft, Save, Trash, User, Users } from 'lucide-react';
import { useEffect, useState } from 'react';

interface CampaignEditProps {
    campaign: Campaign;
    companies: Company[];
    contacts: Contact[];
    selectedContacts: Contact[];
    selectedCompanies?: Company[];
}

export default function CampaignEdit({ campaign, companies, contacts, selectedContacts, selectedCompanies }: CampaignEditProps) {
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

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
        {
            title: 'Edit',
            href: route('campaigns.edit', { campaign: campaign.id }),
        },
    ];

    const { data, setData, put, processing, errors } = useForm({
        name: campaign.name,
        description: campaign.description || '',
        subject: campaign.subject,
        content: campaign.content,
        from_email: campaign.from_email,
        from_name: campaign.from_name || '',
        reply_to: campaign.reply_to || '',
        type: campaign.type || 'contact',
        filter_criteria: campaign.filter_criteria || {
            company_id: null as number | null,
            relevance_min: null as number | null,
            exclude_deal_status: [] as string[],
            job_title: '' as string,
            job_title_category: null as string | null,
        },
        contact_ids: selectedContacts?.map((c) => c.id) || [],
        company_ids: selectedCompanies?.map((c) => c.id) || [],
        schedule_campaign: !!campaign.scheduled_at,
        scheduled_at: campaign.scheduled_at ? new Date(campaign.scheduled_at).toISOString().slice(0, 16) : '',
    });

    // State for filtered contacts based on filter criteria
    const [filteredContacts, setFilteredContacts] = useState<Contact[]>(selectedContacts || []);

    // Unique job titles for autocomplete
    const jobTitles = [...new Set(contacts.map((c) => c.job_title).filter(Boolean) as string[])].sort();

    // Apply filters when filter criteria changes
    useEffect(() => {
        let result = [...contacts];

        // Apply company filter
        if (data.filter_criteria.company_id) {
            result = result.filter((contact) => contact.company_id === data.filter_criteria.company_id);
        }

        // Apply relevance score filter
        if (data.filter_criteria.relevance_min) {
            result = result.filter((contact) => (contact.relevance_score || 0) >= (data.filter_criteria.relevance_min || 0));
        }

        // Apply deal status exclusion filter
        if (data.filter_criteria.exclude_deal_status?.length > 0) {
            result = result.filter((contact) => !data.filter_criteria.exclude_deal_status.includes(contact.deal_status));
        }

        // Apply job title category filter
        if (data.filter_criteria.job_title_category) {
            result = result.filter((contact) => contact.job_title_category === data.filter_criteria.job_title_category);
        }

        // Apply job title filter
        if (data.filter_criteria.job_title) {
            const search = data.filter_criteria.job_title.toLowerCase();
            result = result.filter((contact) => contact.job_title?.toLowerCase().includes(search));
        }

        // Only include contacts with email
        result = result.filter((contact) => !!contact.email);

        setFilteredContacts(result);
    }, [data.filter_criteria, contacts]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Validate based on campaign type
        if (data.type === 'contact') {
            const contactIds = filteredContacts.map((c) => c.id);

            if (contactIds.length === 0) {
                alert('You need to select at least one contact for this campaign');
                return;
            }

            // Use router.put directly with the correct data
            router.put(route('campaigns.update', { campaign: campaign.id }), {
                ...data,
                contact_ids: contactIds,
            });
        } else {
            // For company campaigns
            if (data.company_ids.length === 0) {
                alert('You need to select at least one company for this campaign');
                return;
            }

            router.put(route('campaigns.update', { campaign: campaign.id }), data);
        }
    };

    const handleDelete = () => {
        router.delete(route('campaigns.destroy', { campaign: campaign.id }), {
            onSuccess: () => {
                // Redirect happens automatically with flash message
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Campaign: ${campaign.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {/* Header with back button */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild className="mr-2">
                            <Link href={route('campaigns.show', { campaign: campaign.id })}>
                                <ChevronLeft size={16} />
                                <span>Back to Campaign</span>
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-bold">Edit Campaign</h1>
                    </div>

                    <div className="flex gap-2">
                        <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                            <DialogTrigger asChild>
                                <Button type="button" variant="destructive" size="sm" className="flex items-center gap-1">
                                    <Trash size={16} />
                                    <span>Delete</span>
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Delete Campaign</DialogTitle>
                                    <DialogDescription>
                                        Are you sure you want to delete this campaign? This action cannot be undone.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <Button variant="outline" onClick={() => setDeleteDialogOpen(false)}>
                                        Cancel
                                    </Button>
                                    <Button variant="destructive" onClick={handleDelete}>
                                        Delete Campaign
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>

                        <Button
                            type="button"
                            onClick={() => document.getElementById('campaign-form')?.requestSubmit()}
                            disabled={processing}
                            className="flex items-center gap-1"
                        >
                            <Save size={16} />
                            <span>Save Changes</span>
                        </Button>
                    </div>
                </div>

                <form id="campaign-form" onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        {/* Campaign Details */}
                        <Card className="p-5 md:col-span-2">
                            <h2 className="mb-4 text-lg font-semibold">Campaign Details</h2>

                            <div className="space-y-4">
                                {/* Campaign Type - Read-only for existing campaigns */}
                                <div>
                                    <Label htmlFor="campaign_type">Campaign Type</Label>
                                    <div className="mt-1 mb-2 flex items-center gap-1 text-base">
                                        {data.type === 'company' ? (
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
                                    </div>
                                    <div className="text-xs text-gray-500">
                                        {data.type === 'contact'
                                            ? 'Target individual contacts based on criteria'
                                            : 'Target all contacts within selected companies'}
                                    </div>
                                </div>

                                {/* Campaign Name */}
                                <div>
                                    <Label htmlFor="name">Campaign Name</Label>
                                    <Input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="E.g., Summer Promotion, Product Update"
                                        className={errors.name ? 'border-red-500' : ''}
                                    />
                                    {errors.name && <div className="mt-1 text-sm text-red-500">{errors.name}</div>}
                                </div>

                                {/* Campaign Description */}
                                <div>
                                    <Label htmlFor="description">Description (Optional)</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder="Brief description of this campaign's purpose"
                                        rows={2}
                                    />
                                </div>

                                {/* From Email */}
                                <div>
                                    <Label htmlFor="from_email">From Email</Label>
                                    <Input
                                        id="from_email"
                                        type="email"
                                        value={data.from_email}
                                        onChange={(e) => setData('from_email', e.target.value)}
                                        placeholder="youremail@example.com"
                                        className={errors.from_email ? 'border-red-500' : ''}
                                    />
                                    {errors.from_email && <div className="mt-1 text-sm text-red-500">{errors.from_email}</div>}
                                </div>

                                {/* From Name */}
                                <div>
                                    <Label htmlFor="from_name">From Name (Optional)</Label>
                                    <Input
                                        id="from_name"
                                        type="text"
                                        value={data.from_name}
                                        onChange={(e) => setData('from_name', e.target.value)}
                                        placeholder="Your Name or Company Name"
                                    />
                                </div>

                                {/* Reply-To */}
                                <div>
                                    <Label htmlFor="reply_to">Reply-To Email (Optional)</Label>
                                    <Input
                                        id="reply_to"
                                        type="email"
                                        value={data.reply_to}
                                        onChange={(e) => setData('reply_to', e.target.value)}
                                        placeholder="replies@example.com"
                                    />
                                    <div className="mt-1 text-xs text-gray-500">If left blank, the From Email will be used</div>
                                </div>

                                {/* Schedule Campaign */}
                                <div className="flex items-start space-x-2">
                                    <Checkbox
                                        id="schedule_campaign"
                                        checked={data.schedule_campaign}
                                        onCheckedChange={(checked) => setData('schedule_campaign', checked as boolean)}
                                    />
                                    <div className="grid gap-1.5 leading-none">
                                        <Label
                                            htmlFor="schedule_campaign"
                                            className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                        >
                                            Schedule for later
                                        </Label>
                                        <p className="text-muted-foreground text-sm">Set a specific date and time to send this campaign</p>
                                    </div>
                                </div>

                                {/* Scheduled At - only show if schedule_campaign is true */}
                                {data.schedule_campaign && (
                                    <div>
                                        <Label htmlFor="scheduled_at">Schedule Date & Time</Label>
                                        <Input
                                            id="scheduled_at"
                                            type="datetime-local"
                                            value={data.scheduled_at}
                                            onChange={(e) => setData('scheduled_at', e.target.value)}
                                            min={new Date().toISOString().slice(0, 16)}
                                            className={errors.scheduled_at ? 'border-red-500' : ''}
                                        />
                                        {errors.scheduled_at && <div className="mt-1 text-sm text-red-500">{errors.scheduled_at}</div>}
                                    </div>
                                )}
                            </div>

                            <div className="mt-8 mb-4">
                                <h3 className="mb-4 text-lg font-semibold">Email Content</h3>

                                {/* Subject Line */}
                                <div className="mb-4">
                                    <Label htmlFor="subject">Subject Line</Label>
                                    <Input
                                        id="subject"
                                        type="text"
                                        value={data.subject}
                                        onChange={(e) => setData('subject', e.target.value)}
                                        placeholder="Enter your email subject line"
                                        className={errors.subject ? 'border-red-500' : ''}
                                    />
                                    {errors.subject && <div className="mt-1 text-sm text-red-500">{errors.subject}</div>}
                                </div>

                                {/* Email Content */}
                                <div>
                                    <Label htmlFor="content">Email Body</Label>
                                    <div className="mt-1">
                                        <WysiwygEditor
                                            value={data.content}
                                            onChange={(content) => setData('content', content)}
                                            placeholder="Write your email content here. You can use variables like {{first_name}}, {{last_name}}, {{company}}, etc."
                                            error={!!errors.content}
                                        />
                                    </div>
                                    {errors.content && <div className="mt-1 text-sm text-red-500">{errors.content}</div>}
                                    <div className="mt-1 text-xs text-gray-500">
                                        Available variables: <code>&#123;&#123;first_name&#125;&#125;</code>,{' '}
                                        <code>&#123;&#123;last_name&#125;&#125;</code>, <code>&#123;&#123;full_name&#125;&#125;</code>,{' '}
                                        <code>&#123;&#123;email&#125;&#125;</code>, <code>&#123;&#123;company&#125;&#125;</code>,{' '}
                                        <code>&#123;&#123;job_title&#125;&#125;</code>
                                        {data.type === 'company' && (
                                            <>
                                                , <code>&#123;&#123;recipients&#125;&#125;</code> (list of all people in the company, e.g. "Doug,
                                                Angela and John")
                                            </>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </Card>

                        {/* Target Audience Card */}
                        <Card className="p-5">
                            <h2 className="mb-4 text-lg font-semibold">Target Audience</h2>

                            {/* For Contact-based campaigns */}
                            {data.type === 'contact' && (
                                <div className="space-y-4">
                                    <p className="text-sm text-gray-600">
                                        Select filters to determine which contacts will receive this campaign. Only contacts with valid email
                                        addresses will be included.
                                    </p>

                                    {/* Company Filter */}
                                    <div>
                                        <Label htmlFor="company_filter">Company</Label>
                                        <Select
                                            value={data.filter_criteria.company_id?.toString() || 'all'}
                                            onValueChange={(value) =>
                                                setData('filter_criteria', {
                                                    ...data.filter_criteria,
                                                    company_id: value && value !== 'all' ? parseInt(value) : null,
                                                })
                                            }
                                        >
                                            <SelectTrigger id="company_filter">
                                                <SelectValue placeholder="All Companies" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">All Companies</SelectItem>
                                                {companies.map((company) => (
                                                    <SelectItem key={company.id} value={company.id.toString()}>
                                                        {company.company_name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {/* Relevance Score Filter */}
                                    <div>
                                        <Label htmlFor="relevance_filter">Minimum Relevance Score</Label>
                                        <Select
                                            value={data.filter_criteria.relevance_min?.toString() || 'any'}
                                            onValueChange={(value) =>
                                                setData('filter_criteria', {
                                                    ...data.filter_criteria,
                                                    relevance_min: value && value !== 'any' ? parseInt(value) : null,
                                                })
                                            }
                                        >
                                            <SelectTrigger id="relevance_filter">
                                                <SelectValue placeholder="Any Score" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="any">Any Score</SelectItem>
                                                <SelectItem value="20">20+ (Low)</SelectItem>
                                                <SelectItem value="40">40+ (Medium)</SelectItem>
                                                <SelectItem value="60">60+ (High)</SelectItem>
                                                <SelectItem value="80">80+ (Very High)</SelectItem>
                                                <SelectItem value="100">100 (Perfect Match)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {/* Deal Status Exclusion Filter */}
                                    <div>
                                        <Label className="mb-2 block">Exclude Deal Statuses</Label>
                                        <div className="space-y-2">
                                            {DEAL_STATUSES.map((status) => (
                                                <div key={status} className="flex items-center space-x-2">
                                                    <Checkbox
                                                        id={`status_${status}`}
                                                        checked={data.filter_criteria.exclude_deal_status?.includes(status) || false}
                                                        onCheckedChange={(checked) => {
                                                            const statuses = data.filter_criteria.exclude_deal_status || [];
                                                            const newStatuses = checked
                                                                ? [...statuses, status]
                                                                : statuses.filter((s) => s !== status);

                                                            setData('filter_criteria', {
                                                                ...data.filter_criteria,
                                                                exclude_deal_status: newStatuses,
                                                            });
                                                        }}
                                                    />
                                                    <Label htmlFor={`status_${status}`} className="text-sm capitalize">
                                                        {status.replace('_', ' ')}
                                                    </Label>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Job Title Category Filter */}
                                    <div>
                                        <Label htmlFor="job_title_category_filter">Job Title Category</Label>
                                        <Select
                                            value={data.filter_criteria.job_title_category || 'all'}
                                            onValueChange={(value) =>
                                                setData('filter_criteria', {
                                                    ...data.filter_criteria,
                                                    job_title_category: value && value !== 'all' ? value : null,
                                                })
                                            }
                                        >
                                            <SelectTrigger id="job_title_category_filter">
                                                <SelectValue placeholder="All Categories" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">All Categories</SelectItem>
                                                <SelectItem value="Principal">Principal</SelectItem>
                                                <SelectItem value="Sales">Sales</SelectItem>
                                                <SelectItem value="Operations">Operations</SelectItem>
                                                <SelectItem value="Project Manager">Project Manager</SelectItem>
                                                <SelectItem value="Other">Other</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {/* Job Title Filter */}
                                    <div>
                                        <Label htmlFor="job_title_filter">Job Title</Label>
                                        <Input
                                            id="job_title_filter"
                                            type="text"
                                            list="job-titles-list"
                                            value={data.filter_criteria.job_title || ''}
                                            onChange={(e) =>
                                                setData('filter_criteria', {
                                                    ...data.filter_criteria,
                                                    job_title: e.target.value,
                                                })
                                            }
                                            placeholder="Type to filter by job title..."
                                        />
                                        <datalist id="job-titles-list">
                                            {jobTitles.map((title) => (
                                                <option key={title} value={title} />
                                            ))}
                                        </datalist>
                                    </div>

                                    {/* Contact Count */}
                                    <div className="mt-6 rounded-md bg-gray-50 p-4">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <Users size={16} className="text-gray-500" />
                                                <span className="font-medium">Recipients</span>
                                            </div>
                                            <span className="font-semibold">{filteredContacts.length}</span>
                                        </div>
                                        <div className="mt-1 text-xs text-gray-500">
                                            {filteredContacts.length === 0
                                                ? 'No contacts match your current filters. Adjust the filters to include recipients.'
                                                : `${filteredContacts.length} contacts will receive this campaign.`}
                                        </div>

                                        {/* Show contact names */}
                                        {filteredContacts.length > 0 && (
                                            <div className="mt-3 text-xs">
                                                <div className="font-medium">Selected contacts:</div>
                                                <ul className="list-disc pl-4">
                                                    {filteredContacts.slice(0, 10).map((contact) => (
                                                        <li key={contact.id}>
                                                            {contact.first_name} {contact.last_name} - {contact.email || 'No email'}
                                                        </li>
                                                    ))}
                                                    {filteredContacts.length > 10 && <li>...and {filteredContacts.length - 10} more</li>}
                                                </ul>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* For Company-based campaigns */}
                            {data.type === 'company' && (
                                <div className="space-y-4">
                                    <p className="text-sm text-gray-600">
                                        Select companies to target. All contacts with valid emails in these companies will receive the campaign. The{' '}
                                        <code>&#123;&#123;recipients&#125;&#125;</code> variable will list all contacts in each company.
                                    </p>

                                    {/* Company Selection */}
                                    <div>
                                        <Label className="mb-2 block">Select Companies</Label>
                                        <div className="max-h-96 space-y-2 overflow-y-auto rounded-md border p-3">
                                            {companies &&
                                                companies.map((company) => (
                                                    <div key={company.id} className="flex items-center space-x-2">
                                                        <Checkbox
                                                            id={`company_${company.id}`}
                                                            checked={data.company_ids.includes(company.id)}
                                                            onCheckedChange={(checked) => {
                                                                const newCompanyIds = checked
                                                                    ? [...data.company_ids, company.id]
                                                                    : data.company_ids.filter((id) => id !== company.id);

                                                                setData('company_ids', newCompanyIds);
                                                            }}
                                                        />
                                                        <Label htmlFor={`company_${company.id}`} className="text-sm">
                                                            {company.company_name}
                                                        </Label>
                                                    </div>
                                                ))}
                                        </div>
                                    </div>

                                    {/* Company Count and Summary */}
                                    <div className="mt-6 rounded-md bg-gray-50 p-4">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <Building size={16} className="text-gray-500" />
                                                <span className="font-medium">Selected Companies</span>
                                            </div>
                                            <span className="font-semibold">{data.company_ids.length}</span>
                                        </div>
                                        <div className="mt-1 text-xs text-gray-500">
                                            {data.company_ids.length === 0
                                                ? 'No companies selected. Select at least one company to proceed.'
                                                : `${data.company_ids.length} companies will be targeted in this campaign.`}
                                        </div>

                                        {/* Show selected companies */}
                                        {data.company_ids.length > 0 && (
                                            <div className="mt-3 text-xs">
                                                <div className="font-medium">Selected companies:</div>
                                                <ul className="list-disc pl-4">
                                                    {data.company_ids.map((id) => {
                                                        const company = companies.find((c) => c.id === id);
                                                        return company ? <li key={id}>{company.company_name}</li> : null;
                                                    })}
                                                </ul>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Submit buttons */}
                            <div className="mt-8 space-y-3">
                                <Button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        (data.type === 'contact' && filteredContacts.length === 0) ||
                                        (data.type === 'company' && data.company_ids.length === 0)
                                    }
                                    className="w-full"
                                >
                                    Save Changes
                                </Button>

                                {data.type === 'contact' && filteredContacts.length === 0 && (
                                    <div className="text-center text-xs text-amber-600">
                                        You need to select at least one recipient to update this campaign
                                    </div>
                                )}

                                {data.type === 'company' && data.company_ids.length === 0 && (
                                    <div className="text-center text-xs text-amber-600">
                                        You need to select at least one company to update this campaign
                                    </div>
                                )}

                                <div className="flex justify-between border-t pt-4">
                                    <Button
                                        type="button"
                                        onClick={(e) => {
                                            e.preventDefault();

                                            if (data.type === 'contact') {
                                                const contactIds = filteredContacts.map((c) => c.id);
                                                if (contactIds.length === 0) {
                                                    alert('You need to select at least one contact for this campaign');
                                                    return;
                                                }

                                                router.put(
                                                    route('campaigns.update', { campaign: campaign.id }),
                                                    {
                                                        ...data,
                                                        contact_ids: contactIds,
                                                    },
                                                    {
                                                        onSuccess: () => {
                                                            router.visit(route('campaigns.show', { campaign: campaign.id }));
                                                        },
                                                    },
                                                );
                                            } else {
                                                if (data.company_ids.length === 0) {
                                                    alert('You need to select at least one company for this campaign');
                                                    return;
                                                }

                                                router.put(route('campaigns.update', { campaign: campaign.id }), data, {
                                                    onSuccess: () => {
                                                        router.visit(route('campaigns.show', { campaign: campaign.id }));
                                                    },
                                                });
                                            }
                                        }}
                                        disabled={
                                            processing ||
                                            (data.type === 'contact' && filteredContacts.length === 0) ||
                                            (data.type === 'company' && data.company_ids.length === 0)
                                        }
                                        variant="outline"
                                        size="sm"
                                    >
                                        Save and View Campaign
                                    </Button>

                                    <Button
                                        type="button"
                                        onClick={(e) => {
                                            e.preventDefault();

                                            if (data.type === 'contact') {
                                                const contactIds = filteredContacts.map((c) => c.id);
                                                if (contactIds.length === 0) {
                                                    alert('You need to select at least one contact for this campaign');
                                                    return;
                                                }

                                                router.put(
                                                    route('campaigns.update', { campaign: campaign.id }),
                                                    {
                                                        ...data,
                                                        contact_ids: contactIds,
                                                    },
                                                    {
                                                        onSuccess: () => {
                                                            router.visit(route('campaigns.show', { campaign: campaign.id }));
                                                        },
                                                    },
                                                );
                                            } else {
                                                if (data.company_ids.length === 0) {
                                                    alert('You need to select at least one company for this campaign');
                                                    return;
                                                }

                                                router.put(route('campaigns.update', { campaign: campaign.id }), data, {
                                                    onSuccess: () => {
                                                        router.visit(route('campaigns.show', { campaign: campaign.id }));
                                                    },
                                                });
                                            }
                                        }}
                                        disabled={
                                            processing ||
                                            (data.type === 'contact' && filteredContacts.length === 0) ||
                                            (data.type === 'company' && data.company_ids.length === 0)
                                        }
                                        variant="secondary"
                                        size="sm"
                                        className="flex items-center gap-1"
                                    >
                                        <Calendar className="h-4 w-4" />
                                        <span>Save and Schedule</span>
                                    </Button>
                                </div>
                            </div>
                        </Card>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
