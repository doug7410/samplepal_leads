import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { 
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue 
} from '@/components/ui/select';
import { FormEvent } from 'react';

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
  company: Company;
}

interface ContactEditProps {
  contact: Contact;
  companies: Company[];
}

export default function ContactEdit({ contact, companies }: ContactEditProps) {
  const breadcrumbs: BreadcrumbItem[] = [
    {
      title: 'Dashboard',
      href: '/dashboard',
    },
    {
      title: 'Contacts',
      href: '/contacts',
    },
    {
      title: `${contact.first_name} ${contact.last_name}`,
      href: '#',
    },
  ];

  const { data, setData, put, processing, errors } = useForm({
    company_id: contact.company_id,
    first_name: contact.first_name,
    last_name: contact.last_name,
    email: contact.email || '',
    phone: contact.phone || '',
    job_title: contact.job_title || '',
    has_been_contacted: contact.has_been_contacted,
  });

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    put(route('contacts.update', contact.id));
  };

  const handleCompanyChange = (value: string) => {
    setData('company_id', parseInt(value, 10));
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Edit ${contact.first_name} ${contact.last_name}`} />
      <div className="flex h-full flex-1 flex-col gap-4 p-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Edit Contact</h1>
          <Link href={route('contacts.index')}>
            <Button variant="outline">Back to Contacts</Button>
          </Link>
        </div>
        
        <Card className="p-6">
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
              <div className="space-y-2 md:col-span-2">
                <Label htmlFor="company_id">Company</Label>
                <Select 
                  value={data.company_id.toString()} 
                  onValueChange={handleCompanyChange}
                >
                  <SelectTrigger id="company_id">
                    <SelectValue placeholder="Select company" />
                  </SelectTrigger>
                  <SelectContent>
                    {companies.map((company) => (
                      <SelectItem key={company.id} value={company.id.toString()}>
                        {company.company_name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.company_id && <p className="text-sm text-red-500">{errors.company_id}</p>}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="first_name">First Name</Label>
                <Input
                  id="first_name"
                  name="first_name"
                  value={data.first_name}
                  onChange={e => setData('first_name', e.target.value)}
                  required
                  autoFocus
                />
                {errors.first_name && <p className="text-sm text-red-500">{errors.first_name}</p>}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="last_name">Last Name</Label>
                <Input
                  id="last_name"
                  name="last_name"
                  value={data.last_name}
                  onChange={e => setData('last_name', e.target.value)}
                  required
                />
                {errors.last_name && <p className="text-sm text-red-500">{errors.last_name}</p>}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input
                  id="email"
                  name="email"
                  type="email"
                  value={data.email}
                  onChange={e => setData('email', e.target.value)}
                />
                {errors.email && <p className="text-sm text-red-500">{errors.email}</p>}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="phone">Phone</Label>
                <Input
                  id="phone"
                  name="phone"
                  value={data.phone}
                  onChange={e => setData('phone', e.target.value)}
                />
                {errors.phone && <p className="text-sm text-red-500">{errors.phone}</p>}
              </div>
              
              <div className="space-y-2 md:col-span-2">
                <Label htmlFor="job_title">Job Title</Label>
                <Input
                  id="job_title"
                  name="job_title"
                  value={data.job_title}
                  onChange={e => setData('job_title', e.target.value)}
                />
                {errors.job_title && <p className="text-sm text-red-500">{errors.job_title}</p>}
              </div>
              
              <div className="flex items-center space-x-2 md:col-span-2">
                <Checkbox 
                  id="has_been_contacted" 
                  checked={data.has_been_contacted}
                  onCheckedChange={value => setData('has_been_contacted', Boolean(value))}
                />
                <Label htmlFor="has_been_contacted" className="cursor-pointer">Has been contacted</Label>
                {errors.has_been_contacted && <p className="text-sm text-red-500">{errors.has_been_contacted}</p>}
              </div>
            </div>
            
            <div className="flex justify-end gap-3">
              <Link href={route('contacts.index')}>
                <Button variant="outline" type="button">Cancel</Button>
              </Link>
              <Button type="submit" disabled={processing}>Save Changes</Button>
            </div>
          </form>
        </Card>
      </div>
    </AppLayout>
  );
}