import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
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
import { NotesField } from '@/components/notes-field'; 
import { FormEvent, useState, useCallback, Fragment } from 'react';

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
  notes: string | null;
  company: Company;
}

interface ContactEditProps {
  contact: Contact;
  companies: Company[];
  errors?: Record<string, string>;
}

export default function ContactEdit({ contact, companies, errors = {} }: ContactEditProps) {
  // Use React's useState instead of Inertia's useForm
  const [formData, setFormData] = useState({
    company_id: contact.company_id,
    first_name: contact.first_name,
    last_name: contact.last_name,
    email: contact.email || '',
    phone: contact.phone || '',
    job_title: contact.job_title || '',
    has_been_contacted: contact.has_been_contacted,
    notes: contact.notes || '',
  });
  
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Handle text input changes
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value,
    }));
  };

  // Handle checkbox changes
  const handleCheckboxChange = (checked: boolean) => {
    setFormData(prev => ({
      ...prev,
      has_been_contacted: checked,
    }));
  };

  // Handle company select changes
  const handleCompanyChange = (value: string) => {
    setFormData(prev => ({
      ...prev,
      company_id: parseInt(value, 10),
    }));
  };

  // Handle form submission with Inertia
  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    
    router.put(route('contacts.update', contact.id), formData, {
      onFinish: () => setIsSubmitting(false),
    });
  };

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
                  value={formData.company_id.toString()} 
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
                  value={formData.first_name}
                  onChange={handleInputChange}
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
                  value={formData.last_name}
                  onChange={handleInputChange}
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
                  value={formData.email}
                  onChange={handleInputChange}
                />
                {errors.email && <p className="text-sm text-red-500">{errors.email}</p>}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="phone">Phone</Label>
                <Input
                  id="phone"
                  name="phone"
                  value={formData.phone}
                  onChange={handleInputChange}
                />
                {errors.phone && <p className="text-sm text-red-500">{errors.phone}</p>}
              </div>
              
              <div className="space-y-2 md:col-span-2">
                <Label htmlFor="job_title">Job Title</Label>
                <Input
                  id="job_title"
                  name="job_title"
                  value={formData.job_title}
                  onChange={handleInputChange}
                />
                {errors.job_title && <p className="text-sm text-red-500">{errors.job_title}</p>}
              </div>
              
              <div className="md:col-span-2">
                <NotesField
                  initialValue={formData.notes}
                  onValueChange={(value) => {
                    // Only update form data on debounced change events
                    setFormData(prev => ({
                      ...prev,
                      notes: value,
                    }));
                  }}
                  error={errors.notes}
                  placeholder="Add any notes about this contact here..."
                  rows={6}
                />
              </div>
              
              <div className="flex items-center space-x-2 md:col-span-2">
                <Checkbox 
                  id="has_been_contacted" 
                  checked={formData.has_been_contacted}
                  onCheckedChange={handleCheckboxChange}
                />
                <Label htmlFor="has_been_contacted" className="cursor-pointer">Has been contacted</Label>
                {errors.has_been_contacted && <p className="text-sm text-red-500">{errors.has_been_contacted}</p>}
              </div>
            </div>
            
            <div className="flex justify-end gap-3">
              <Link href={route('contacts.index')}>
                <Button variant="outline" type="button">Cancel</Button>
              </Link>
              <Button type="submit" disabled={isSubmitting}>Save Changes</Button>
            </div>
          </form>
        </Card>
      </div>
    </AppLayout>
  );
}