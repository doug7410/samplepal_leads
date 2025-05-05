import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { UserPlus, Users } from 'lucide-react';
import { cn } from '@/lib/utils';
import { CompanyFilters } from '@/components/companies/company-filters';
import { Pagination } from '@/components/ui/pagination';
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
}

// Function to convert text to title case
function toTitleCase(text: string): string {
  return text
    .toLowerCase()
    .split(' ')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
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

export default function CompaniesIndex({ companies, filters, filterOptions }: CompaniesIndexProps) {
  // Memoize filter props to prevent unnecessary re-renders
  const initialFilters = useMemo(() => ({
    search: filters.search || '',
    city: filters.city || 'all',
    state: filters.state || 'all',
  }), [filters.search, filters.city, filters.state]);

  const memoizedFilterOptions = useMemo(() => ({
    cities: filterOptions.cities,
    states: filterOptions.states,
  }), [filterOptions.cities, filterOptions.states]);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Companies" />
      <div className="flex h-full flex-1 flex-col gap-4 p-4">
        <h1 className="text-2xl font-bold">Companies</h1>

        <CompanyFilters 
          initialFilters={initialFilters}
          filterOptions={memoizedFilterOptions}
        />

        <Card className="overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b bg-neutral-50 text-left text-sm font-medium text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800">
                  <th className="px-4 py-3">Actions</th>
                  <th className="px-4 py-3">Contacts</th>
                  <th className="px-4 py-3">Manufacturer</th>
                  <th className="px-4 py-3">Company Name</th>
                  <th className="px-4 py-3">City/Region</th>
                  <th className="px-4 py-3">State</th>
                  <th className="px-4 py-3">Zip Code</th>
                  <th className="px-4 py-3">Email</th>
                  <th className="px-4 py-3">Phone</th>
                  <th className="px-4 py-3">Website</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                {companies.data.map((company) => (
                  <tr
                    key={company.id}
                    className="hover:bg-neutral-100 dark:hover:bg-neutral-800"
                  >
                    <td className="whitespace-nowrap px-4 py-3">
                      <Link href={route('contacts.create', { company_id: company.id })}>
                        <Button size="sm" variant="ghost" className="flex items-center gap-1">
                          <UserPlus size={16} />
                          <span>Add Contact</span>
                        </Button>
                      </Link>
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-center">
                      <Link
                        href={route('contacts.index', { company_id: company.id })}
                        className="inline-flex items-center gap-1 text-blue-600 hover:underline dark:text-blue-400"
                      >
                        <Users size={14} />
                        <span>{company.contacts_count || 0}</span>
                      </Link>
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm capitalize">{company.manufacturer}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm font-medium">{toTitleCase(company.company_name)}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">{company.city_or_region ? toTitleCase(company.city_or_region) : '-'}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">{company.state || '-'}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      {company.zip_code
                        ? company.zip_code.split('-')[0].substring(0, 5)
                        : '-'}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">{company.email || '-'}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      {company.company_phone
                        ? company.company_phone.split(' EXT')[0].split(' x')[0].split(' ext')[0]
                        : '-'}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
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
                      {(filters.search || (filters.city && filters.city !== 'all') || (filters.state && filters.state !== 'all')) ? (
                        <div>
                          <p>No companies found matching the selected filters:</p>
                          <ul className="list-disc list-inside mt-1">
                            {filters.search && <li>Company Name: "{filters.search}"</li>}
                            {filters.city && filters.city !== 'all' && <li>City: {toTitleCase(filters.city)}</li>}
                            {filters.state && filters.state !== 'all' && <li>State: {filters.state}</li>}
                          </ul>
                        </div>
                      ) : (
                        "No companies found"
                      )}
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
          
          {companies.data.length > 0 && (
            <div className="border-t border-neutral-200 dark:border-neutral-700 px-4">
              <Pagination
                total={companies.total}
                perPage={companies.per_page}
                currentPage={companies.current_page}
                from={companies.from}
                to={companies.to}
                links={companies.links}
                preserveScroll={true}
                preserveState={true}
                only={['companies']}
              />
            </div>
          )}
        </Card>
      </div>
    </AppLayout>
  );
}
