import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Building2, CheckCircle, Edit, FilterIcon, Mail, MessageCircle, Trash2, X, XCircle } from 'lucide-react';

interface Company {
    id: number;
    company_name: string;
}

interface Contact {
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
    created_at: string;
    updated_at: string;
    company: Company;
}

interface ContactsIndexProps {
    contacts: Contact[];
    companies: {
        id: number;
        company_name: string;
    }[];
    filters: {
        company_id?: number;
        deal_status?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Contacts',
        href: '/contacts',
    },
];

export default function ContactsIndex({ contacts, companies, filters }: ContactsIndexProps) {
    // Determine company name if we're filtering by company
    const companyName = filters.company_id && contacts.length > 0 ? contacts[0].company.company_name : null;

    // Handle company filter change
    const handleCompanyChange = (value: string) => {
        router.get(
            route('contacts.index'),
            {
                ...filters,
                company_id: value === 'all' ? null : value,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    // Handle deal status filter change
    const handleDealStatusChange = (value: string) => {
        router.get(
            route('contacts.index'),
            {
                ...filters,
                deal_status: value === 'all' ? null : value,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    // Handle contact deletion
    const handleDelete = (contact: Contact) => {
        if (confirm(`Are you sure you want to delete ${contact.first_name} ${contact.last_name}? This action cannot be undone.`)) {
            router.delete(route('contacts.destroy', { id: contact.id }));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={companyName ? `${companyName} Contacts` : 'Contacts'} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <h1 className="text-2xl font-bold">
                            {companyName ? (
                                <>
                                    <span className="text-neutral-500">Contacts for</span> {companyName}
                                </>
                            ) : (
                                'All Contacts'
                            )}
                        </h1>
                        {filters.company_id && (
                            <div className="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-800/30 dark:text-blue-400">
                                Filtered
                            </div>
                        )}
                    </div>
                    <Button asChild>
                        <Link href={route('companies.index')} className="flex items-center gap-1">
                            <Building2 size={16} />
                            <span>View Companies</span>
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
                            {/* Company Filter */}
                            <div className="space-y-2">
                                <label htmlFor="company-filter" className="text-sm font-medium">
                                    Company
                                </label>
                                <Select value={filters.company_id?.toString() || 'all'} onValueChange={handleCompanyChange}>
                                    <SelectTrigger id="company-filter" className="w-full">
                                        <SelectValue placeholder="Filter by company" />
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

                            {/* Deal Status Filter */}
                            <div className="space-y-2">
                                <label htmlFor="deal-status-filter" className="text-sm font-medium">
                                    Deal Status
                                </label>
                                <Select value={filters.deal_status || 'all'} onValueChange={handleDealStatusChange}>
                                    <SelectTrigger id="deal-status-filter" className="w-full">
                                        <SelectValue placeholder="Filter by status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Statuses</SelectItem>
                                        <SelectItem value="none">None</SelectItem>
                                        <SelectItem value="contacted">Contacted</SelectItem>
                                        <SelectItem value="responded">Responded</SelectItem>
                                        <SelectItem value="in_progress">In Progress</SelectItem>
                                        <SelectItem value="closed_won">Closed (Won)</SelectItem>
                                        <SelectItem value="closed_lost">Closed (Lost)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Reset filters button - only show when filters are active */}
                            {(filters.company_id || filters.deal_status) && (
                                <div className="flex items-end">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="flex items-center gap-1"
                                        onClick={() => router.get(route('contacts.index'))}
                                    >
                                        <X size={14} />
                                        <span>Reset Filters</span>
                                    </Button>
                                </div>
                            )}
                        </div>
                    </div>
                </Card>

                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b bg-neutral-50 text-left text-sm font-medium text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800">
                                    <th className="px-4 py-3">First Name</th>
                                    <th className="px-4 py-3">Last Name</th>
                                    <th className="px-4 py-3">Email</th>
                                    <th className="px-4 py-3">Phone</th>
                                    <th className="px-4 py-3">Job Title</th>
                                    <th className="px-4 py-3">Company</th>
                                    <th className="px-4 py-3">Deal Status</th>
                                    <th className="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                {contacts.map((contact) => (
                                    <tr key={contact.id} className="hover:bg-neutral-100 dark:hover:bg-neutral-800">
                                        <td className="px-4 py-3 text-sm font-medium whitespace-nowrap">{contact.first_name}</td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">{contact.last_name}</td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">
                                            {contact.email ? (
                                                <a href={`mailto:${contact.email}`} className="text-blue-600 hover:underline dark:text-blue-400">
                                                    {contact.email}
                                                </a>
                                            ) : (
                                                '-'
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">
                                            {contact.cell_phone ? contact.cell_phone.split(' EXT')[0].split(' x')[0].split(' ext')[0] : '-'}
                                        </td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">{contact.job_title || '-'}</td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">
                                            <Link href={route('companies.index')} className="text-blue-600 hover:underline dark:text-blue-400">
                                                {contact.company?.company_name || '-'}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">
                                            {/* Deal status badge with appropriate color */}
                                            {(() => {
                                                // Define badge colors based on deal status
                                                const statusColors: Record<string, string> = {
                                                    none: 'bg-gray-100 text-gray-800',
                                                    contacted: 'bg-blue-100 text-blue-800',
                                                    responded: 'bg-purple-100 text-purple-800',
                                                    in_progress: 'bg-yellow-100 text-yellow-800',
                                                    closed_won: 'bg-green-100 text-green-800',
                                                    closed_lost: 'bg-red-100 text-red-800',
                                                };

                                                // Define icons based on deal status
                                                const statusIcons: Record<string, any> = {
                                                    none: null,
                                                    contacted: Mail,
                                                    responded: MessageCircle,
                                                    in_progress: null,
                                                    closed_won: CheckCircle,
                                                    closed_lost: XCircle,
                                                };

                                                const Icon = statusIcons[contact.deal_status];

                                                return (
                                                    <Badge className={statusColors[contact.deal_status]}>
                                                        {Icon && <Icon size={12} className="mr-1" />}
                                                        <span className="capitalize">{contact.deal_status.replace('_', ' ')}</span>
                                                    </Badge>
                                                );
                                            })()}
                                        </td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">
                                            <div className="flex items-center gap-1">
                                                <Link href={route('contacts.edit', { id: contact.id })}>
                                                    <Button size="sm" variant="ghost" className="flex items-center gap-1">
                                                        <Edit size={16} />
                                                        <span>Edit</span>
                                                    </Button>
                                                </Link>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    className="flex items-center gap-1 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    onClick={() => handleDelete(contact)}
                                                >
                                                    <Trash2 size={16} />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}

                                {contacts.length === 0 && (
                                    <tr>
                                        <td colSpan={8} className="px-4 py-6 text-center text-neutral-500">
                                            No contacts found
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
