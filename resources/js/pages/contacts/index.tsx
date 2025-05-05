import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Building2, Edit, FilterIcon, UserPlus, X } from 'lucide-react';
import { 
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue 
} from '@/components/ui/select';

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
  phone: string | null;
  job_title: string | null;
  has_been_contacted: boolean;
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
  const companyName = filters.company_id && contacts.length > 0 
    ? contacts[0].company.company_name 
    : null;
    
  // Handle company filter change
  const handleCompanyChange = (value: string) => {
    router.get(route('contacts.index'), {
      company_id: value === 'all' ? null : value
    }, {
      preserveState: true,
      replace: true
    });
  };
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={companyName ? `${companyName} Contacts` : "Contacts"} />
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
            
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div className="space-y-2">
                <label htmlFor="company-filter" className="text-sm font-medium">
                  Company
                </label>
                <Select 
                  value={filters.company_id?.toString() || 'all'} 
                  onValueChange={handleCompanyChange}
                >
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
              
              {/* Reset filters button - only show when filters are active */}
              {filters.company_id && (
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
                  <th className="px-4 py-3">Contacted</th>
                  <th className="px-4 py-3">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                {contacts.map((contact) => (
                  <tr 
                    key={contact.id} 
                    className="hover:bg-neutral-100 dark:hover:bg-neutral-800"
                  >
                    <td className="whitespace-nowrap px-4 py-3 text-sm font-medium">{contact.first_name}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">{contact.last_name}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      {contact.email ? (
                        <a 
                          href={`mailto:${contact.email}`}
                          className="text-blue-600 hover:underline dark:text-blue-400"
                        >
                          {contact.email}
                        </a>
                      ) : (
                        '-'
                      )}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      {contact.phone ? (
                        contact.phone.split(' EXT')[0].split(' x')[0].split(' ext')[0]
                      ) : (
                        '-'
                      )}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">{contact.job_title || '-'}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      <Link 
                        href={route('companies.index')}
                        className="text-blue-600 hover:underline dark:text-blue-400"
                      >
                        {contact.company?.company_name || '-'}
                      </Link>
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      <Checkbox 
                        checked={contact.has_been_contacted}
                        onCheckedChange={() => {
                          // Use Inertia's router to make the POST request with CSRF protection
                          router.post(route('contacts.toggle-contacted', { id: contact.id }));
                        }}
                      />
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                      <Link href={route('contacts.edit', { id: contact.id })}>
                        <Button size="sm" variant="ghost" className="flex items-center gap-1">
                          <Edit size={16} />
                          <span>Edit</span>
                        </Button>
                      </Link>
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