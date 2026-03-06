import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import WysiwygEditor from '@/components/wysiwyg-editor';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Company, type Contact } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Building, ChevronLeft, Save, Users } from 'lucide-react';
import { useMemo, useState } from 'react';

function htmlToPlainText(html: string): string {
    let text = html.replace(/<a\s[^>]*href=["']([^"']+)["'][^>]*>(.*?)<\/a>/gis, '$2 ($1)');
    text = text.replace(/<li[^>]*>/gi, '\n- ');
    text = text.replace(/<br\s*\/?>/gi, '\n');
    text = text.replace(/<\/(p|h[1-6]|div|tr)>/gi, '\n\n');
    text = text.replace(/<\/li>/gi, '\n');
    const tmp = document.createElement('div');
    tmp.innerHTML = text;
    text = tmp.textContent ?? tmp.innerText ?? '';
    text = text.replace(/\n{3,}/g, '\n\n');
    return text.trim();
}

interface CampaignCreateProps {
    companies: Company[];
    contacts: Contact[];
}

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
        title: 'Create Campaign',
        href: '/campaigns/create',
    },
];

export default function CampaignCreate({ companies, contacts }: CampaignCreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        subject: '',
        content: '',
        from_email: '',
        from_name: '',
        reply_to: '',
        type: 'contact', // Default to contact campaign
        contact_ids: [] as number[],
        company_ids: [] as number[],
        schedule_campaign: false,
        scheduled_at: '',
    });

    const [searchQuery, setSearchQuery] = useState('');
    const plainTextPreview = useMemo(() => htmlToPlainText(data.content), [data.content]);

    const visibleContacts = useMemo(() => {
        const eligible = (contacts || []).filter((c) => Boolean(c.email) && !c.has_unsubscribed);
        if (!searchQuery.trim()) return eligible;
        const q = searchQuery.toLowerCase();
        return eligible.filter((c) => {
            const fullName = `${c.first_name} ${c.last_name}`.toLowerCase();
            const company = c.company?.company_name?.toLowerCase() ?? '';
            return fullName.includes(q) || company.includes(q);
        });
    }, [contacts, searchQuery]);

    const handleSubmit = (e: React.FormEvent, asDraft = true) => {
        e.preventDefault();

        if (data.type === 'contact') {
            if (data.contact_ids.length === 0) {
                alert('You need to select at least one contact for this campaign');
                return;
            }
        } else {
            if (data.company_ids.length === 0) {
                alert('You need to select at least one company for this campaign');
                return;
            }
        }

        post(route('campaigns.store'), {
            onError: (errors) => {
                console.error('Campaign creation failed:', errors);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Campaign" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {/* Header with back button */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild className="mr-2">
                            <Link href={route('campaigns.index')}>
                                <ChevronLeft size={16} />
                                <span>Back to Campaigns</span>
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-bold">Create Campaign</h1>
                    </div>

                    <div className="flex gap-2">
                        <Button
                            type="button"
                            onClick={(e) => handleSubmit(e, true)}
                            disabled={processing}
                            variant="outline"
                            className="flex items-center gap-1"
                        >
                            <Save size={16} />
                            <span>Save as Draft</span>
                        </Button>
                    </div>
                </div>

                <form onSubmit={(e) => handleSubmit(e, true)}>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        {/* Campaign Details */}
                        <Card className="p-5 md:col-span-2">
                            <h2 className="mb-4 text-lg font-semibold">Campaign Details</h2>

                            <div className="space-y-4">
                                {/* Campaign Type */}
                                <div>
                                    <Label htmlFor="campaign_type">Campaign Type</Label>
                                    <Select
                                        value={data.type}
                                        onValueChange={(value) => {
                                            setData('type', value);
                                            // Reset the appropriate IDs when switching types
                                            if (value === 'contact') {
                                                setData('company_ids', []);
                                            } else {
                                                setData('contact_ids', []);
                                            }
                                        }}
                                    >
                                        <SelectTrigger id="campaign_type">
                                            <SelectValue placeholder="Select Campaign Type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="contact">Individual Contacts</SelectItem>
                                            <SelectItem value="company">Company (All Contacts)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div className="mt-1 text-xs text-gray-500">
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
                                        value={data.description || ''}
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
                                        value={data.from_name || ''}
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
                                        value={data.reply_to || ''}
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

                                    {/* Plain text preview */}
                                    {data.content && (
                                        <div className="mt-3 rounded-md border">
                                            <div className="border-b px-4 py-2 text-sm font-medium text-gray-500">Plain Text Preview</div>
                                            <div className="p-4">
                                                <pre className="whitespace-pre-wrap font-mono text-sm text-gray-700">{plainTextPreview}</pre>
                                            </div>
                                        </div>
                                    )}

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

                        {/* Target Audience */}
                        <Card className="p-5">
                            <h2 className="mb-4 text-lg font-semibold">Target Audience</h2>

                            {/* For Contact-based campaigns */}
                            {data.type === 'contact' && (
                                <div className="space-y-3">
                                    <Input
                                        type="text"
                                        placeholder="Search by name or company..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                    />

                                    <div className="flex gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setData('contact_ids', visibleContacts.map((c) => c.id))}
                                        >
                                            Select All
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setData('contact_ids', [])}
                                        >
                                            Clear All
                                        </Button>
                                    </div>

                                    <div className="max-h-80 space-y-2 overflow-y-auto rounded-md border p-3">
                                        {visibleContacts.length === 0 ? (
                                            <div className="py-2 text-center text-sm text-gray-500">No contacts found</div>
                                        ) : (
                                            visibleContacts.map((contact) => (
                                                <div key={contact.id} className="flex items-center space-x-2">
                                                    <Checkbox
                                                        id={`contact_${contact.id}`}
                                                        checked={data.contact_ids.includes(contact.id)}
                                                        onCheckedChange={(checked) => {
                                                            const newIds = checked
                                                                ? [...data.contact_ids, contact.id]
                                                                : data.contact_ids.filter((id) => id !== contact.id);
                                                            setData('contact_ids', newIds);
                                                        }}
                                                    />
                                                    <Label htmlFor={`contact_${contact.id}`} className="cursor-pointer text-sm">
                                                        {contact.first_name} {contact.last_name}
                                                        {contact.company && (
                                                            <span className="ml-1 text-gray-500">{contact.company.company_name}</span>
                                                        )}
                                                    </Label>
                                                </div>
                                            ))
                                        )}
                                    </div>

                                    <div className="rounded-md bg-gray-50 p-4">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <Users size={16} className="text-gray-500" />
                                                <span className="font-medium">Selected</span>
                                            </div>
                                            <span className="font-semibold">{data.contact_ids.length}</span>
                                        </div>
                                        {data.contact_ids.length > 0 && (
                                            <div className="mt-3 text-xs">
                                                <div className="font-medium">Selected contacts:</div>
                                                <ul className="list-disc pl-4">
                                                    {data.contact_ids.slice(0, 10).map((id) => {
                                                        const contact = contacts.find((c) => c.id === id);
                                                        return contact ? (
                                                            <li key={id}>
                                                                {contact.first_name} {contact.last_name}
                                                            </li>
                                                        ) : null;
                                                    })}
                                                    {data.contact_ids.length > 10 && <li>...and {data.contact_ids.length - 10} more</li>}
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
                                        (data.type === 'contact' && data.contact_ids.length === 0) ||
                                        (data.type === 'company' && data.company_ids.length === 0)
                                    }
                                    className="w-full"
                                >
                                    Create Campaign
                                </Button>

                                {data.type === 'contact' && data.contact_ids.length === 0 && (
                                    <div className="text-center text-xs text-amber-600">
                                        You need to select at least one recipient to create a campaign
                                    </div>
                                )}

                                {data.type === 'company' && data.company_ids.length === 0 && (
                                    <div className="text-center text-xs text-amber-600">
                                        You need to select at least one company to create a campaign
                                    </div>
                                )}
                            </div>
                        </Card>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
