import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Building2, Users } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <Link href={route('companies.index')}>
                        <Card className="flex aspect-video items-center justify-center hover:bg-neutral-50 dark:hover:bg-neutral-800">
                            <div className="text-center">
                                <Building2 size={32} className="mx-auto mb-2" />
                                <h2 className="text-xl font-semibold">Companies</h2>
                                <p className="text-neutral-500">View all companies</p>
                            </div>
                        </Card>
                    </Link>
                    <Link href={route('contacts.index')}>
                        <Card className="flex aspect-video items-center justify-center hover:bg-neutral-50 dark:hover:bg-neutral-800">
                            <div className="text-center">
                                <Users size={32} className="mx-auto mb-2" />
                                <h2 className="text-xl font-semibold">Contacts</h2>
                                <p className="text-neutral-500">View all contacts</p>
                            </div>
                        </Card>
                    </Link>
                    <div className="border-sidebar-border/70 dark:border-sidebar-border relative aspect-video overflow-hidden rounded-xl border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
                <div className="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border md:min-h-min">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </AppLayout>
    );
}
