import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Building2, UserPlus } from 'lucide-react';

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

export default function ContactsIndex({ contacts }: ContactsIndexProps) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Contacts" />
      <div className="flex h-full flex-1 flex-col gap-4 p-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Contacts</h1>
          <Link href={route('companies.index')}>
            <Button className="flex items-center gap-1">
              <Building2 size={16} />
              <span>View Companies</span>
            </Button>
          </Link>
        </div>
        
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
                  </tr>
                ))}
                
                {contacts.length === 0 && (
                  <tr>
                    <td colSpan={7} className="px-4 py-6 text-center text-neutral-500">
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