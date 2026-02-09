import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DEAL_STATUSES, dealStatusBadgeColors, dealStatusIcons, dealStatusLabels } from '@/constants/deal-status';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown, Building2, Edit, FilterIcon, Trash2, X } from 'lucide-react';
import { useMemo, useState } from 'react';

import type { DealStatus } from '@/constants/deal-status';

interface Company {
    id: number;
    company_name: string;
    website: string | null;
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
    job_title_category: string | null;
    has_been_contacted: boolean;
    deal_status: DealStatus;
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
    jobTitles: string[];
    jobCategories: string[];
    filters: {
        company_id?: number;
        deal_status?: string;
        job_title?: string;
        job_title_category?: string;
        has_email?: string;
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

export default function ContactsIndex({ contacts, companies, jobTitles, jobCategories, filters }: ContactsIndexProps) {
    const [sortKey, setSortKey] = useState<string | null>(null);
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');

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

    const handleJobTitleChange = (value: string) => {
        router.get(
            route('contacts.index'),
            {
                ...filters,
                job_title: value === 'all' ? null : value,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleJobCategoryChange = (value: string) => {
        router.get(
            route('contacts.index'),
            {
                ...filters,
                job_title_category: value === 'all' ? null : value,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleEmailFilterChange = (value: string) => {
        router.get(
            route('contacts.index'),
            {
                ...filters,
                has_email: value === 'all' ? null : value,
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
        if (!sortKey) return contacts;

        return [...contacts].sort((a, b) => {
            let aVal: string | null = null;
            let bVal: string | null = null;

            switch (sortKey) {
                case 'name':
                    aVal = `${a.last_name || ''} ${a.first_name || ''}`.toLowerCase();
                    bVal = `${b.last_name || ''} ${b.first_name || ''}`.toLowerCase();
                    break;
                case 'email':
                    aVal = (a.email || '').toLowerCase();
                    bVal = (b.email || '').toLowerCase();
                    break;
                case 'phone':
                    aVal = (a.cell_phone || '').toLowerCase();
                    bVal = (b.cell_phone || '').toLowerCase();
                    break;
                case 'job_title':
                    aVal = (a.job_title || '').toLowerCase();
                    bVal = (b.job_title || '').toLowerCase();
                    break;
                case 'job_category':
                    aVal = (a.job_title_category || '').toLowerCase();
                    bVal = (b.job_title_category || '').toLowerCase();
                    break;
                case 'website':
                    aVal = (a.company?.website || '').toLowerCase();
                    bVal = (b.company?.website || '').toLowerCase();
                    break;
                case 'company':
                    aVal = (a.company?.company_name || '').toLowerCase();
                    bVal = (b.company?.company_name || '').toLowerCase();
                    break;
                case 'deal_status':
                    aVal = a.deal_status;
                    bVal = b.deal_status;
                    break;
            }

            if (aVal === null || bVal === null) return 0;
            if (aVal < bVal) return sortDir === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortDir === 'asc' ? 1 : -1;
            return 0;
        });
    }, [contacts, sortKey, sortDir]);

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
                        <span className="rounded-full bg-neutral-100 px-2.5 py-0.5 text-sm font-medium text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                            {contacts.length}
                        </span>
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

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
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

                            {/* Job Title Filter */}
                            <div className="space-y-2">
                                <label htmlFor="job-title-filter" className="text-sm font-medium">
                                    Job Title
                                </label>
                                <Select value={filters.job_title || 'all'} onValueChange={handleJobTitleChange}>
                                    <SelectTrigger id="job-title-filter" className="w-full">
                                        <SelectValue placeholder="Filter by job title" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Job Titles</SelectItem>
                                        {jobTitles.map((title) => (
                                            <SelectItem key={title} value={title}>
                                                {title}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Job Category Filter */}
                            <div className="space-y-2">
                                <label htmlFor="job-category-filter" className="text-sm font-medium">
                                    Job Category
                                </label>
                                <Select value={filters.job_title_category || 'all'} onValueChange={handleJobCategoryChange}>
                                    <SelectTrigger id="job-category-filter" className="w-full">
                                        <SelectValue placeholder="Filter by category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Categories</SelectItem>
                                        {jobCategories.map((category) => (
                                            <SelectItem key={category} value={category}>
                                                {category}
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
                                        {DEAL_STATUSES.map((status) => (
                                            <SelectItem key={status} value={status}>
                                                {dealStatusLabels[status]}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Email Filter */}
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Email</label>
                                <RadioGroup value={filters.has_email || 'all'} onValueChange={handleEmailFilterChange} className="flex gap-4 pt-1">
                                    {[
                                        { value: 'all', label: 'All' },
                                        { value: 'with', label: 'With' },
                                        { value: 'without', label: 'Without' },
                                    ].map((option) => (
                                        <label key={option.value} className="flex cursor-pointer items-center gap-1.5 text-sm">
                                            <RadioGroupItem value={option.value} />
                                            {option.label}
                                        </label>
                                    ))}
                                </RadioGroup>
                            </div>

                            {/* Reset filters button - only show when filters are active */}
                            {(filters.company_id || filters.deal_status || filters.job_title || filters.job_title_category || filters.has_email) && (
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
                                    <th
                                        className="cursor-pointer px-4 py-3 hover:text-neutral-700 dark:hover:text-neutral-300"
                                        onClick={() => toggleSort('name')}
                                    >
                                        <span className="flex items-center gap-1">
                                            <SortIcon columnKey="name" /> Name
                                        </span>
                                    </th>
                                    <th
                                        className="cursor-pointer px-4 py-3 hover:text-neutral-700 dark:hover:text-neutral-300"
                                        onClick={() => toggleSort('email')}
                                    >
                                        <span className="flex items-center gap-1">
                                            <SortIcon columnKey="email" /> Email
                                        </span>
                                    </th>
                                    <th
                                        className="cursor-pointer px-4 py-3 hover:text-neutral-700 dark:hover:text-neutral-300"
                                        onClick={() => toggleSort('website')}
                                    >
                                        <span className="flex items-center gap-1">
                                            <SortIcon columnKey="website" /> Website
                                        </span>
                                    </th>
                                    <th
                                        className="cursor-pointer px-4 py-3 hover:text-neutral-700 dark:hover:text-neutral-300"
                                        onClick={() => toggleSort('phone')}
                                    >
                                        <span className="flex items-center gap-1">
                                            <SortIcon columnKey="phone" /> Phone
                                        </span>
                                    </th>
                                    <th
                                        className="cursor-pointer px-4 py-3 hover:text-neutral-700 dark:hover:text-neutral-300"
                                        onClick={() => toggleSort('job_title')}
                                    >
                                        <span className="flex items-center gap-1">
                                            <SortIcon columnKey="job_title" /> Job Title
                                        </span>
                                    </th>
                                    <th
                                        className="cursor-pointer px-4 py-3 hover:text-neutral-700 dark:hover:text-neutral-300"
                                        onClick={() => toggleSort('job_category')}
                                    >
                                        <span className="flex items-center gap-1">
                                            <SortIcon columnKey="job_category" /> Job Category
                                        </span>
                                    </th>
                                    <th
                                        className="cursor-pointer px-4 py-3 hover:text-neutral-700 dark:hover:text-neutral-300"
                                        onClick={() => toggleSort('company')}
                                    >
                                        <span className="flex items-center gap-1">
                                            <SortIcon columnKey="company" /> Company
                                        </span>
                                    </th>
                                    <th
                                        className="cursor-pointer px-4 py-3 hover:text-neutral-700 dark:hover:text-neutral-300"
                                        onClick={() => toggleSort('deal_status')}
                                    >
                                        <span className="flex items-center gap-1">
                                            <SortIcon columnKey="deal_status" /> Deal Status
                                        </span>
                                    </th>
                                    <th className="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                {sortedContacts.map((contact) => (
                                    <tr key={contact.id} className="hover:bg-neutral-100 dark:hover:bg-neutral-800">
                                        <td className="px-4 py-3 text-sm font-medium whitespace-nowrap">{contact.first_name} {contact.last_name}</td>
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
                                            {contact.company?.website ? (
                                                <a
                                                    href={contact.company.website.startsWith('http') ? contact.company.website : `https://${contact.company.website}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-blue-600 hover:underline dark:text-blue-400"
                                                >
                                                    {contact.company.website.replace(/^https?:\/\//, '')}
                                                </a>
                                            ) : (
                                                '-'
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">
                                            {contact.cell_phone ? contact.cell_phone.split(' EXT')[0].split(' x')[0].split(' ext')[0] : '-'}
                                        </td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">{contact.job_title || '-'}</td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">{contact.job_title_category || '-'}</td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">
                                            <Link href={route('companies.index')} className="text-blue-600 hover:underline dark:text-blue-400">
                                                {contact.company?.company_name || '-'}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">
                                            {(() => {
                                                const Icon = dealStatusIcons[contact.deal_status];
                                                return (
                                                    <Badge className={dealStatusBadgeColors[contact.deal_status]}>
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
                                        <td colSpan={9} className="px-4 py-6 text-center text-neutral-500">
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
