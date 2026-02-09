import type { LucideIcon } from 'lucide-react';
import { CheckCircle, Mail, MessageCircle, XCircle } from 'lucide-react';

export const DEAL_STATUSES = ['none', 'contacted', 'responded', 'in_progress', 'closed_won', 'closed_lost'] as const;

export type DealStatus = (typeof DEAL_STATUSES)[number];

export const dealStatusLabels: Record<DealStatus, string> = {
    none: 'New Leads',
    contacted: 'Contacted',
    responded: 'Responded',
    in_progress: 'In Progress',
    closed_won: 'Customers',
    closed_lost: 'Lost',
};

export const dealStatusColors: Record<DealStatus, string> = {
    none: 'bg-gray-100 text-gray-700',
    contacted: 'bg-blue-100 text-blue-700',
    responded: 'bg-purple-100 text-purple-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    closed_won: 'bg-green-100 text-green-700',
    closed_lost: 'bg-red-100 text-red-700',
};

export const dealStatusBadgeColors: Record<DealStatus, string> = {
    none: 'bg-gray-100 text-gray-800',
    contacted: 'bg-blue-100 text-blue-800',
    responded: 'bg-purple-100 text-purple-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    closed_won: 'bg-green-100 text-green-800',
    closed_lost: 'bg-red-100 text-red-800',
};

export const dealStatusIcons: Record<DealStatus, LucideIcon | null> = {
    none: null,
    contacted: Mail,
    responded: MessageCircle,
    in_progress: null,
    closed_won: CheckCircle,
    closed_lost: XCircle,
};
