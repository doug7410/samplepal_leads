import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { type BreadcrumbItem, type Campaign, type Company, type Contact } from '@/types';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { 
  ChevronLeft, 
  Save, 
  Send,
  Calendar, 
  User,
  Users,
  Building,
  CheckCircle2,
  Trash
} from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { 
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue 
} from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { 
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { useEffect, useState } from 'react';

interface CampaignEditProps {
  campaign: Campaign;
  companies: Company[];
  contacts: Contact[];
  selectedContacts: Contact[];
}

export default function CampaignEdit({ campaign, companies, contacts, selectedContacts }: CampaignEditProps) {
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
    filter_criteria: campaign.filter_criteria || {
      company_id: null as number | null,
      relevance_min: null as number | null,
      deal_status: [] as string[],
    },
    contact_ids: selectedContacts?.map(c => c.id) || [],
    schedule_campaign: !!campaign.scheduled_at,
    scheduled_at: campaign.scheduled_at ? new Date(campaign.scheduled_at).toISOString().slice(0, 16) : '',
  });

  // State for filtered contacts based on filter criteria
  const [filteredContacts, setFilteredContacts] = useState<Contact[]>(selectedContacts || []);
  
  // Apply filters when filter criteria changes
  useEffect(() => {
    let result = [...contacts];
    
    // Apply company filter
    if (data.filter_criteria.company_id) {
      result = result.filter(contact => 
        contact.company_id === data.filter_criteria.company_id
      );
    }
    
    // Apply relevance score filter
    if (data.filter_criteria.relevance_min) {
      result = result.filter(contact => 
        (contact.relevance_score || 0) >= (data.filter_criteria.relevance_min || 0)
      );
    }
    
    // Apply deal status filter
    if (data.filter_criteria.deal_status.length > 0) {
      result = result.filter(contact => 
        data.filter_criteria.deal_status.includes(contact.deal_status)
      );
    }
    
    // Only include contacts with email
    result = result.filter(contact => !!contact.email);
    
    setFilteredContacts(result);
  }, [data.filter_criteria, contacts]);
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Set contact IDs based on filtered contacts
    const contactIds = filteredContacts.map(c => c.id);
    setData('contact_ids', contactIds);
    
    // Submit the form
    put(route('campaigns.update', { campaign: campaign.id }), {
      data: {
        ...data,
        contact_ids: contactIds, // Ensure contact IDs are included in the submission
      },
      onSuccess: () => {
        // Redirect happens automatically with flash message
      },
    });
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
                <Button
                  type="button"
                  variant="destructive"
                  size="sm"
                  className="flex items-center gap-1"
                >
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
                  <Button
                    variant="outline"
                    onClick={() => setDeleteDialogOpen(false)}
                  >
                    Cancel
                  </Button>
                  <Button
                    variant="destructive"
                    onClick={handleDelete}
                  >
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
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {/* Campaign Details */}
            <Card className="md:col-span-2 p-5">
              <h2 className="text-lg font-semibold mb-4">Campaign Details</h2>
              
              <div className="space-y-4">
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
                  {errors.name && (
                    <div className="text-red-500 text-sm mt-1">{errors.name}</div>
                  )}
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
                  {errors.from_email && (
                    <div className="text-red-500 text-sm mt-1">{errors.from_email}</div>
                  )}
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
                  <div className="text-xs text-gray-500 mt-1">
                    If left blank, the From Email will be used
                  </div>
                </div>
                
                {/* Schedule Campaign */}
                <div className="flex items-start space-x-2">
                  <Checkbox
                    id="schedule_campaign"
                    checked={data.schedule_campaign}
                    onCheckedChange={(checked) => 
                      setData('schedule_campaign', checked as boolean)
                    }
                  />
                  <div className="grid gap-1.5 leading-none">
                    <Label
                      htmlFor="schedule_campaign"
                      className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                      Schedule for later
                    </Label>
                    <p className="text-sm text-muted-foreground">
                      Set a specific date and time to send this campaign
                    </p>
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
                    {errors.scheduled_at && (
                      <div className="text-red-500 text-sm mt-1">{errors.scheduled_at}</div>
                    )}
                  </div>
                )}
              </div>
              
              <div className="mt-8 mb-4">
                <h3 className="text-lg font-semibold mb-4">Email Content</h3>
                
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
                  {errors.subject && (
                    <div className="text-red-500 text-sm mt-1">{errors.subject}</div>
                  )}
                </div>
                
                {/* Email Content */}
                <div>
                  <Label htmlFor="content">Email Body</Label>
                  <Textarea
                    id="content"
                    value={data.content}
                    onChange={(e) => setData('content', e.target.value)}
                    placeholder="Write your email content here. You can use variables like {{first_name}}, {{last_name}}, {{company}}, etc."
                    rows={12}
                    className={errors.content ? 'border-red-500' : ''}
                  />
                  {errors.content && (
                    <div className="text-red-500 text-sm mt-1">{errors.content}</div>
                  )}
                  <div className="text-xs text-gray-500 mt-1">
                    Available variables: <code>&#123;&#123;first_name&#125;&#125;</code>, <code>&#123;&#123;last_name&#125;&#125;</code>, <code>&#123;&#123;full_name&#125;&#125;</code>, <code>&#123;&#123;email&#125;&#125;</code>, <code>&#123;&#123;company&#125;&#125;</code>, <code>&#123;&#123;job_title&#125;&#125;</code>
                  </div>
                </div>
              </div>
            </Card>
            
            {/* Contact Filter */}
            <Card className="p-5">
              <h2 className="text-lg font-semibold mb-4">Target Audience</h2>
              
              <div className="space-y-4">
                <p className="text-sm text-gray-600">
                  Select filters to determine which contacts will receive this campaign.
                  Only contacts with valid email addresses will be included.
                </p>
                
                {/* Company Filter */}
                <div>
                  <Label htmlFor="company_filter">Company</Label>
                  <Select
                    value={data.filter_criteria.company_id?.toString() || 'all'}
                    onValueChange={(value) => 
                      setData('filter_criteria', {
                        ...data.filter_criteria,
                        company_id: value && value !== 'all' ? parseInt(value) : null
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
                        relevance_min: value && value !== 'any' ? parseInt(value) : null
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
                
                {/* Deal Status Filter */}
                <div>
                  <Label className="mb-2 block">Deal Status</Label>
                  <div className="space-y-2">
                    {['none', 'contacted', 'responded', 'in_progress', 'closed_won', 'closed_lost'].map((status) => (
                      <div key={status} className="flex items-center space-x-2">
                        <Checkbox
                          id={`status_${status}`}
                          checked={data.filter_criteria.deal_status?.includes(status) || false}
                          onCheckedChange={(checked) => {
                            const statuses = data.filter_criteria.deal_status || [];
                            const newStatuses = checked
                              ? [...statuses, status]
                              : statuses.filter(s => s !== status);
                            
                            setData('filter_criteria', {
                              ...data.filter_criteria,
                              deal_status: newStatuses
                            });
                          }}
                        />
                        <Label
                          htmlFor={`status_${status}`}
                          className="text-sm capitalize"
                        >
                          {status.replace('_', ' ')}
                        </Label>
                      </div>
                    ))}
                  </div>
                </div>
                
                {/* Contact Count */}
                <div className="mt-6 p-4 bg-gray-50 rounded-md">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <Users size={16} className="text-gray-500" />
                      <span className="font-medium">Recipients</span>
                    </div>
                    <span className="font-semibold">{filteredContacts.length}</span>
                  </div>
                  <div className="text-xs text-gray-500 mt-1">
                    {filteredContacts.length === 0 
                      ? "No contacts match your current filters. Adjust the filters to include recipients."
                      : `${filteredContacts.length} contacts will receive this campaign.`
                    }
                  </div>
                </div>
              </div>
              
              {/* Submit buttons */}
              <div className="mt-8 space-y-3">
                <Button
                  type="submit"
                  disabled={processing || filteredContacts.length === 0}
                  className="w-full"
                >
                  Save Changes
                </Button>
                
                {filteredContacts.length === 0 && (
                  <div className="text-amber-600 text-xs text-center">
                    You need to select at least one recipient to update this campaign
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