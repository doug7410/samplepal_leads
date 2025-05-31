import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Company {
    id: number;
    company_name: string;
}

export interface Contact {
    id: number;
    company_id: number;
    first_name: string;
    last_name: string;
    email: string | null;
    cell_phone: string | null;
    office_phone: string | null;
    job_title: string | null;
    has_been_contacted: boolean;
    deal_status: 'none' | 'contacted' | 'responded' | 'in_progress' | 'closed_won' | 'closed_lost';
    notes: string | null;
    relevance_score: number | null;
    created_at: string;
    updated_at: string;
    company: Company;
}

export interface Campaign {
    id: number;
    name: string;
    description: string | null;
    subject: string;
    content: string;
    from_email: string;
    from_name: string | null;
    reply_to: string | null;
    status: 'draft' | 'scheduled' | 'in_progress' | 'completed' | 'paused' | 'failed';
    type: 'contact' | 'company';
    scheduled_at: string | null;
    completed_at: string | null;
    user_id: number;
    filter_criteria: any | null;
    created_at: string;
    updated_at: string;
    user: User;
    campaign_contacts?: CampaignContact[];
    companies?: Company[];
}

export interface CampaignContact {
    id: number;
    campaign_id: number;
    contact_id: number;
    status: 'pending' | 'processing' | 'sent' | 'delivered' | 'opened' | 'clicked' | 'responded' | 'bounced' | 'failed' | 'demo_scheduled';
    message_id: string | null;
    sent_at: string | null;
    delivered_at: string | null;
    opened_at: string | null;
    clicked_at: string | null;
    responded_at: string | null;
    failed_at: string | null;
    failure_reason: string | null;
    created_at: string;
    updated_at: string;
    contact: Contact;
}

export interface EmailEvent {
    id: number;
    campaign_id: number;
    contact_id: number;
    message_id: string | null;
    event_type: string;
    event_time: string;
    event_data: any | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    updated_at: string;
}
