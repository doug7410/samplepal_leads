import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Companies', href: '/companies' },
    { title: 'Add Company', href: '#' },
];

export default function CompanyCreate() {
    const { data, setData, post, processing, errors } = useForm({
        company_name: '',
        manufacturer: '',
        company_phone: '',
        email: '',
        website: '',
        address_line_1: '',
        address_line_2: '',
        city_or_region: '',
        state: '',
        zip_code: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('companies.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Company" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Add Company</h1>
                    <Link href={route('companies.index')}>
                        <Button variant="outline">Back to Companies</Button>
                    </Link>
                </div>

                <Card className="p-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="company_name">Company Name *</Label>
                                <Input
                                    id="company_name"
                                    value={data.company_name}
                                    onChange={(e) => setData('company_name', e.target.value)}
                                    required
                                    autoFocus
                                />
                                {errors.company_name && <p className="text-sm text-red-500">{errors.company_name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="manufacturer">Manufacturer</Label>
                                <Input
                                    id="manufacturer"
                                    value={data.manufacturer}
                                    onChange={(e) => setData('manufacturer', e.target.value)}
                                />
                                {errors.manufacturer && <p className="text-sm text-red-500">{errors.manufacturer}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="company_phone">Phone</Label>
                                <Input
                                    id="company_phone"
                                    value={data.company_phone}
                                    onChange={(e) => setData('company_phone', e.target.value)}
                                />
                                {errors.company_phone && <p className="text-sm text-red-500">{errors.company_phone}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                />
                                {errors.email && <p className="text-sm text-red-500">{errors.email}</p>}
                            </div>

                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="website">Website</Label>
                                <Input
                                    id="website"
                                    value={data.website}
                                    onChange={(e) => setData('website', e.target.value)}
                                    placeholder="example.com"
                                />
                                {errors.website && <p className="text-sm text-red-500">{errors.website}</p>}
                            </div>

                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="address_line_1">Address</Label>
                                <Input
                                    id="address_line_1"
                                    value={data.address_line_1}
                                    onChange={(e) => setData('address_line_1', e.target.value)}
                                    placeholder="Street address"
                                />
                                {errors.address_line_1 && <p className="text-sm text-red-500">{errors.address_line_1}</p>}
                            </div>

                            <div className="space-y-2 md:col-span-2">
                                <Input
                                    id="address_line_2"
                                    value={data.address_line_2}
                                    onChange={(e) => setData('address_line_2', e.target.value)}
                                    placeholder="Suite, unit, etc. (optional)"
                                />
                                {errors.address_line_2 && <p className="text-sm text-red-500">{errors.address_line_2}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="city_or_region">City / Region</Label>
                                <Input
                                    id="city_or_region"
                                    value={data.city_or_region}
                                    onChange={(e) => setData('city_or_region', e.target.value)}
                                />
                                {errors.city_or_region && <p className="text-sm text-red-500">{errors.city_or_region}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="state">State</Label>
                                    <Input
                                        id="state"
                                        value={data.state}
                                        onChange={(e) => setData('state', e.target.value)}
                                        placeholder="e.g. CA"
                                    />
                                    {errors.state && <p className="text-sm text-red-500">{errors.state}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="zip_code">Zip Code</Label>
                                    <Input
                                        id="zip_code"
                                        value={data.zip_code}
                                        onChange={(e) => setData('zip_code', e.target.value)}
                                    />
                                    {errors.zip_code && <p className="text-sm text-red-500">{errors.zip_code}</p>}
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end gap-3">
                            <Link href={route('companies.index')}>
                                <Button variant="outline" type="button">
                                    Cancel
                                </Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                Add Company
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </AppLayout>
    );
}
