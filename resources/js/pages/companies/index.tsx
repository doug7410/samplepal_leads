import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { UserPlus, Users, Search, X, MapPin, Map } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Input } from '@/components/ui/input';
import { 
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

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
  const { data, setData, get, processing } = useForm({
    search: filters.search || '',
    city: filters.city || 'all',
    state: filters.state || 'all',
  });

  const handleSearch = () => {
    get(route('companies.index', {}), {
      data: {
        search: data.search,
        city: data.city,
        state: data.state,
      },
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleClearSearch = () => {
    // Update the form state
    setData({
      search: '',
      city: 'all',
      state: 'all',
    });
    
    // Navigate to the base URL without any query parameters
    router.visit(route('companies.index'), {
      replace: true,
      preserveState: true,
      preserveScroll: true,
      only: ['companies']
    });
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      handleSearch();
    }
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Companies" />
      <div className="flex h-full flex-1 flex-col gap-4 p-4">
        <h1 className="text-2xl font-bold">Companies</h1>
        
        <Card className="p-4 mb-4">
          <div className="flex flex-col space-y-4 max-w-[700px]">
            <div className="flex items-center gap-2">
              <Search size={16} className="text-neutral-500" />
              <h2 className="text-sm font-medium">Filter Companies</h2>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div className="relative flex-1">
                <div className="flex items-center gap-2 mb-2">
                  <Search size={14} className="text-neutral-500" />
                  <span className="text-sm font-medium">Company Name</span>
                </div>
                <div className="relative">
                  <Input
                    type="text"
                    placeholder="Search by company name..."
                    value={data.search}
                    onChange={(e) => setData('search', e.target.value)}
                    onKeyDown={handleKeyDown}
                    className="pr-8"
                  />
                  {data.search && (
                    <button
                      type="button"
                      onClick={() => setData('search', '')}
                      className="absolute right-2 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600"
                    >
                      <X size={16} />
                    </button>
                  )}
                </div>
              </div>
              
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-2">
                  <MapPin size={14} className="text-neutral-500" />
                  <span className="text-sm font-medium">City/Region</span>
                </div>
                <Select 
                  value={data.city}
                  onValueChange={(value) => setData('city', value)}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select city" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Cities</SelectItem>
                    {filterOptions.cities.map((city) => (
                      <SelectItem key={city} value={city || "empty"}>
                        {toTitleCase(city)}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-2">
                  <Map size={14} className="text-neutral-500" />
                  <span className="text-sm font-medium">State</span>
                </div>
                <Select 
                  value={data.state}
                  onValueChange={(value) => setData('state', value)}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select state" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All States</SelectItem>
                    {filterOptions.states.map((state) => (
                      <SelectItem key={state} value={state || "empty"}>
                        {state}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            
            <div className="flex items-center gap-3 mt-2">
              <Button 
                onClick={handleSearch} 
                disabled={processing}
                size="sm"
              >
                Apply Filters
              </Button>
              {(data.search || (data.city && data.city !== 'all') || (data.state && data.state !== 'all')) && (
                <Button 
                  onClick={handleClearSearch} 
                  disabled={processing}
                  size="sm"
                  variant="outline"
                >
                  Reset All
                </Button>
              )}
            </div>
          </div>
        </Card>
        
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
                
                {companies.length === 0 && (
                  <tr>
                    <td colSpan={10} className="px-4 py-6 text-center text-neutral-500">
                      {(data.search || (data.city && data.city !== 'all') || (data.state && data.state !== 'all')) ? (
                        <div>
                          <p>No companies found matching the selected filters:</p>
                          <ul className="list-disc list-inside mt-1">
                            {data.search && <li>Company Name: "{data.search}"</li>}
                            {data.city && data.city !== 'all' && <li>City: {toTitleCase(data.city)}</li>}
                            {data.state && data.state !== 'all' && <li>State: {data.state}</li>}
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
        </Card>
      </div>
    </AppLayout>
  );
}