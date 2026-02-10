import { CompanyFilters } from '@/components/companies/company-filters';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Pagination } from '@/components/ui/pagination';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown, Trash2, UserPlus, Users } from 'lucide-react';
import { useMemo } from 'react';

interface Company {
    id: number;
    manufacturer: string;
    company_name: string;
    company_phone: string | null;
    city_or_region: string | null;
    state: string | null;
    zip_code: string | null;
    email: string | null;
    website: string | null;
    contacts_count: number;
    deleted_at: string | null;
    created_at: string;
    updated_at: string;
}

interface CompaniesIndexProps {
    companies: {
        data: Company[];
        current_page: number;
        per_page: number;
        from: number;
        to: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: {
        search: string;
        city: string;
        state: string;
    };
    filterOptions: {
        cities: string[];
        states: string[];
    };
    sort: {
        field: string;
        direction: string;
    };
}

interface SortHeaderProps {
    field: string;
    label: string;
    currentSortField: string;
    currentSortDirection: string;
    onSort: (field: string) => void;
}

const SortHeader = ({ field, label, currentSortField, currentSortDirection, onSort }: SortHeaderProps) => {
    const isActive = currentSortField === field;
    const icon = isActive ? (
        currentSortDirection === 'asc' ? (
            <ArrowUp className="ml-1 h-4 w-4" />
        ) : (
            <ArrowDown className="ml-1 h-4 w-4" />
        )
    ) : (
        <ArrowUpDown className="ml-1 h-4 w-4 opacity-50" />
    );

    const isContactsColumn = field === 'contacts_count';

    return (
        <th className={`px-4 py-3 ${isContactsColumn ? 'text-center' : ''}`}>
            <button
                className={`inline-flex items-center text-sm font-medium text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-300 ${isContactsColumn ? 'justify-center' : ''}`}
                onClick={() => onSort(field)}
            >
                {label}
                {icon}
            </button>
        </th>
    );
};

// Function to convert text to title case
function toTitleCase(text: string): string {
    return text
        .toLowerCase()
        .split(' ')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Companies',
        href: '/companies',
    },
];

export default function CompaniesIndex({ companies, filters, filterOptions, sort }: CompaniesIndexProps) {
    // Memoize filter props to prevent unnecessary re-renders
    const initialFilters = useMemo(
        () => ({
            search: filters.search || '',
            city: filters.city || 'all',
            state: filters.state || 'all',
        }),
        [filters.search, filters.city, filters.state],
    );

    const memoizedFilterOptions = useMemo(
        () => ({
            cities: filterOptions.cities,
            states: filterOptions.states,
        }),
        [filterOptions.cities, filterOptions.states],
    );

    // Handle sorting
    const handleSort = (field: string) => {
        const direction = sort.field === field && sort.direction === 'asc' ? 'desc' : 'asc';

        router.get(
            route('companies.index'),
            {
                ...filters,
                sort: field,
                direction,
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['companies', 'sort'],
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Companies" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-2xl font-bold">Companies</h1>

                <CompanyFilters initialFilters={initialFilters} filterOptions={memoizedFilterOptions} />

                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b bg-neutral-50 text-left text-sm font-medium text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800">
                                    <th className="px-4 py-3">Actions</th>
                                    <SortHeader
                                        field="contacts_count"
                                        label="Contacts"
                                        currentSortField={sort.field}
                                        currentSortDirection={sort.direction}
                                        onSort={handleSort}
                                    />
                                    <th className="px-4 py-3">Manufacturer</th>
                                    <SortHeader
                                        field="company_name"
                                        label="Company Name"
                                        currentSortField={sort.field}
                                        currentSortDirection={sort.direction}
                                        onSort={handleSort}
                                    />
                                    <SortHeader
                                        field="city_or_region"
                                        label="City/Region"
                                        currentSortField={sort.field}
                                        currentSortDirection={sort.direction}
                                        onSort={handleSort}
                                    />
                                    <th className="px-4 py-3">State</th>
                                    <th className="px-4 py-3">Zip Code</th>
                                    <th className="px-4 py-3">Email</th>
                                    <th className="px-4 py-3">Phone</th>
                                    <th className="px-4 py-3">Website</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                {companies.data.map((company) => (
                                    <tr key={company.id} className="hover:bg-neutral-100 dark:hover:bg-neutral-800">
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            <div className="flex items-center gap-1">
                                                {!company.deleted_at && (
                                                    <>
                                                        <Button size="sm" variant="ghost" className="flex items-center gap-1" asChild>
                                                            <Link href={route('contacts.create', { company_id: company.id })}>
                                                                <UserPlus size={16} />
                                                                <span>Add Contact</span>
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            className="text-red-600 hover:text-red-700"
                                                            onClick={() => {
                                                                if (confirm(`Delete "${company.company_name}"? Contacts from this company will be excluded from future campaigns.`)) {
                                                                    router.delete(route('companies.destroy', { company: company.id }));
                                                                }
                                                            }}
                                                        >
                                                            <Trash2 size={16} />
                                                        </Button>
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-center text-sm font-medium whitespace-nowrap">
                                            <Link
                                                href={route('contacts.index', { company_id: company.id })}
                                                className="inline-flex items-center gap-1 text-blue-600 hover:underline dark:text-blue-400"
                                            >
                                                <Users size={14} />
                                                <span>{company.contacts_count || 0}</span>
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap capitalize">{company.manufacturer}</td>
                                        <td className="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                            {toTitleCase(company.company_name)}
                                            {company.deleted_at && <Badge className="ml-1 bg-red-100 text-red-800">Deleted</Badge>}
                                        </td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">
                                            {company.city_or_region ? toTitleCase(company.city_or_region) : '-'}
                                        </td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">{company.state || '-'}</td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">
                                            {company.zip_code ? company.zip_code.split('-')[0].substring(0, 5) : '-'}
                                        </td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">{company.email || '-'}</td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">
                                            {company.company_phone ? company.company_phone.split(' EXT')[0].split(' x')[0].split(' ext')[0] : '-'}
                                        </td>
                                        <td className="px-4 py-3 text-sm whitespace-nowrap">
                                            {company.website ? (
                                                <a
                                                    href={company.website.startsWith('http') ? company.website : `https://${company.website}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-blue-600 hover:underline dark:text-blue-400"
                                                >
                                                    {company.website}
                                                </a>
                                            ) : (
                                                '-'
                                            )}
                                        </td>
                                    </tr>
                                ))}

                                {companies.data.length === 0 && (
                                    <tr>
                                        <td colSpan={10} className="px-4 py-6 text-center text-neutral-500">
                                            {filters.search ||
                                            (filters.city && filters.city !== 'all') ||
                                            (filters.state && filters.state !== 'all') ? (
                                                <div>
                                                    <p>No companies found matching the selected filters:</p>
                                                    <ul className="mt-1 list-inside list-disc">
                                                        {filters.search && <li>Company Name: "{filters.search}"</li>}
                                                        {filters.city && filters.city !== 'all' && <li>City: {toTitleCase(filters.city)}</li>}
                                                        {filters.state && filters.state !== 'all' && <li>State: {filters.state}</li>}
                                                    </ul>
                                                </div>
                                            ) : (
                                                'No companies found'
                                            )}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {companies.data.length > 0 && (
                        <div className="border-t border-neutral-200 px-4 dark:border-neutral-700">
                            <Pagination
                                total={companies.total}
                                perPage={companies.per_page}
                                currentPage={companies.current_page}
                                from={companies.from}
                                to={companies.to}
                                links={companies.links}
                                preserveScroll={true}
                                preserveState={true}
                                only={['companies', 'sort']}
                            />
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
