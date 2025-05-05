import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { UserPlus } from 'lucide-react';

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
  companies: Company[];
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

export default function CompaniesIndex({ companies }: CompaniesIndexProps) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Companies" />
      <div className="flex h-full flex-1 flex-col gap-4 p-4">
        <h1 className="text-2xl font-bold">Companies</h1>
        
        <Card className="overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b bg-neutral-50 text-left text-sm font-medium text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800">
                  <th className="px-4 py-3">Actions</th>
                  <th className="px-4 py-3">Contacts</th>
                  <th className="px-4 py-3">Manufacturer</th>
                  <th className="px-4 py-3">Company Name</th>
                  <th className="px-4 py-3">Phone</th>
                  <th className="px-4 py-3">City/Region</th>
                  <th className="px-4 py-3">State</th>
                  <th className="px-4 py-3">Zip Code</th>
                  <th className="px-4 py-3">Email</th>
                  <th className="px-4 py-3">Website</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                {companies.map((company) => (
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
                      {company.contacts_count || 0}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm capitalize">{company.manufacturer}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm font-medium">{company.company_name}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      {company.company_phone 
                        ? company.company_phone.split(' EXT')[0].split(' x')[0].split(' ext')[0]
                        : '-'}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">{company.city_or_region || '-'}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">{company.state || '-'}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      {company.zip_code 
                        ? company.zip_code.split('-')[0].substring(0, 5)
                        : '-'}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">{company.email || '-'}</td>
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
                
                {companies.length === 0 && (
                  <tr>
                    <td colSpan={10} className="px-4 py-6 text-center text-neutral-500">
                      No companies found
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