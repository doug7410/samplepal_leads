import { NotesField } from '@/components/notes-field';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface ContactCreateProps {
    company_id: number;
    errors?: Record<string, string>;
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

export default function ContactCreate({ company_id, errors = {} }: ContactCreateProps) {
    // Use React's useState instead of Inertia's useForm
    const [formData, setFormData] = useState({
        company_id: company_id,
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        job_title: '',
        has_been_contacted: false,
        notes: '',
    });

    const [isSubmitting, setIsSubmitting] = useState(false);

    // Handle text input changes
    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const { name, value } = e.target;
        setFormData((prev) => ({
            ...prev,
            [name]: value,
        }));
    };

    // Handle checkbox changes
    const handleCheckboxChange = (checked: boolean) => {
        setFormData((prev) => ({
            ...prev,
            has_been_contacted: checked,
        }));
    };

    // Handle form submission with Inertia
    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.post(route('contacts.store'), formData, {
            onFinish: () => setIsSubmitting(false),
        });
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
                                    value={formData.first_name}
                                    onChange={handleInputChange}
                                    required
                                    autoFocus
                                />
                                {errors.first_name && <p className="text-sm text-red-500">{errors.first_name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="last_name">Last Name</Label>
                                <Input id="last_name" name="last_name" value={formData.last_name} onChange={handleInputChange} required />
                                {errors.last_name && <p className="text-sm text-red-500">{errors.last_name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" name="email" type="email" value={formData.email} onChange={handleInputChange} />
                                {errors.email && <p className="text-sm text-red-500">{errors.email}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" name="phone" value={formData.phone} onChange={handleInputChange} />
                                {errors.phone && <p className="text-sm text-red-500">{errors.phone}</p>}
                            </div>

                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="job_title">Job Title</Label>
                                <Input id="job_title" name="job_title" value={formData.job_title} onChange={handleInputChange} />
                                {errors.job_title && <p className="text-sm text-red-500">{errors.job_title}</p>}
                            </div>

                            <div className="md:col-span-2">
                                <NotesField
                                    initialValue={formData.notes}
                                    onValueChange={(value) => {
                                        // Only update form data on debounced change events
                                        setFormData((prev) => ({
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
                                <Checkbox id="has_been_contacted" checked={formData.has_been_contacted} onCheckedChange={handleCheckboxChange} />
                                <Label htmlFor="has_been_contacted" className="cursor-pointer">
                                    Has been contacted
                                </Label>
                                {errors.has_been_contacted && <p className="text-sm text-red-500">{errors.has_been_contacted}</p>}
                            </div>
                        </div>

                        <div className="flex justify-end gap-3">
                            <Link href={route('companies.index')}>
                                <Button variant="outline" type="button">
                                    Cancel
                                </Button>
                            </Link>
                            <Button type="submit" disabled={isSubmitting}>
                                Create Contact
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </AppLayout>
    );
}
