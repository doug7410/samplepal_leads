import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FormEvent } from 'react';

interface ContactCreateProps {
  company_id: number;
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
  {
    title: 'Create Contact',
    href: '#',
  },
];

export default function ContactCreate({ company_id }: ContactCreateProps) {
  const { data, setData, post, processing, errors } = useForm({
    company_id: company_id,
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    job_title: '',
  });

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    post(route('contacts.store'));
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Create Contact" />
      <div className="flex h-full flex-1 flex-col gap-4 p-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Create Contact</h1>
          <Link href={route('companies.index')}>
            <Button variant="outline">Back to Companies</Button>
          </Link>
        </div>
        
        <Card className="p-6">
          <form onSubmit={handleSubmit} className="space-y-6">
            <input type="hidden" name="company_id" value={company_id} />
            
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
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
            </div>
            
            <div className="flex justify-end gap-3">
              <Link href={route('companies.index')}>
                <Button variant="outline" type="button">Cancel</Button>
              </Link>
              <Button type="submit" disabled={processing}>Create Contact</Button>
            </div>
          </form>
        </Card>
      </div>
    </AppLayout>
  );
}