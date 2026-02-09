import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { dealStatusColors, dealStatusLabels } from '@/constants/deal-status';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Building2, Clock, Mail, Target, TrendingUp, Users } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface DashboardProps {
    stats: {
        activeCampaigns: number;
        totalCampaigns: number;
        openRate: number;
        totalSent: number;
    };
    pipeline: Record<string, number>;
    needsFollowUp: number;
    availableLeads: number;
}

export default function Dashboard({ stats, pipeline, needsFollowUp, availableLeads }: DashboardProps) {
    const totalContacts = Object.values(pipeline).reduce((a, b) => a + b, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                {/* Quick Stats Row */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-4">
                    <Card className="p-4">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-blue-100 p-2">
                                <Mail size={20} className="text-blue-600" />
                            </div>
                            <div>
                                <p className="text-sm text-neutral-500">Active Campaigns</p>
                                <p className="text-2xl font-semibold">{stats.activeCampaigns}</p>
                            </div>
                        </div>
                    </Card>

                    <Card className="p-4">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-green-100 p-2">
                                <TrendingUp size={20} className="text-green-600" />
                            </div>
                            <div>
                                <p className="text-sm text-neutral-500">Open Rate (30d)</p>
                                <p className="text-2xl font-semibold">{stats.openRate}%</p>
                            </div>
                        </div>
                    </Card>

                    <Card className="p-4">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-purple-100 p-2">
                                <Target size={20} className="text-purple-600" />
                            </div>
                            <div>
                                <p className="text-sm text-neutral-500">Available Leads</p>
                                <p className="text-2xl font-semibold">{availableLeads}</p>
                            </div>
                        </div>
                    </Card>

                    <Card className="p-4">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-amber-100 p-2">
                                <Clock size={20} className="text-amber-600" />
                            </div>
                            <div>
                                <p className="text-sm text-neutral-500">Need Follow-up</p>
                                <p className="text-2xl font-semibold">{needsFollowUp}</p>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Main Content Row */}
                <div className="grid gap-4 md:grid-cols-3">
                    {/* Quick Actions */}
                    <Card className="p-5">
                        <h2 className="mb-4 text-lg font-semibold">Quick Actions</h2>
                        <div className="space-y-3">
                            <Button variant="outline" className="w-full justify-start" asChild>
                                <Link href={route('campaigns.create')}>
                                    <Mail size={16} className="mr-2" />
                                    Create Campaign
                                </Link>
                            </Button>
                            <Button variant="outline" className="w-full justify-start" asChild>
                                <Link href={route('contacts.index')}>
                                    <Users size={16} className="mr-2" />
                                    View Contacts
                                </Link>
                            </Button>
                            <Button variant="outline" className="w-full justify-start" asChild>
                                <Link href={route('companies.index')}>
                                    <Building2 size={16} className="mr-2" />
                                    View Companies
                                </Link>
                            </Button>
                            <Button variant="outline" className="w-full justify-start" asChild>
                                <Link href={route('campaigns.index')}>
                                    <TrendingUp size={16} className="mr-2" />
                                    View Campaigns
                                </Link>
                            </Button>
                        </div>
                    </Card>

                    {/* Pipeline Breakdown */}
                    <Card className="p-5 md:col-span-2">
                        <h2 className="mb-4 text-lg font-semibold">Contact Pipeline</h2>
                        <div className="space-y-3">
                            {Object.entries(dealStatusLabels).map(([status, label]) => {
                                const count = pipeline[status] || 0;
                                const percentage = totalContacts > 0 ? (count / totalContacts) * 100 : 0;
                                return (
                                    <div key={status}>
                                        <div className="mb-1 flex justify-between text-sm">
                                            <span className={`rounded px-2 py-0.5 text-xs font-medium ${dealStatusColors[status]}`}>{label}</span>
                                            <span className="text-neutral-600">{count}</span>
                                        </div>
                                        <div className="h-2 overflow-hidden rounded-full bg-neutral-100">
                                            <div
                                                className={`h-full ${status === 'closed_won' ? 'bg-green-500' : status === 'closed_lost' ? 'bg-red-400' : 'bg-blue-500'}`}
                                                style={{ width: `${percentage}%` }}
                                            />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                        <div className="mt-4 border-t pt-4 text-sm text-neutral-500">Total: {totalContacts} contacts</div>
                    </Card>
                </div>

                {/* Navigation Cards */}
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
                    <Link href={route('campaigns.index')}>
                        <Card className="flex aspect-video items-center justify-center hover:bg-neutral-50 dark:hover:bg-neutral-800">
                            <div className="text-center">
                                <Mail size={32} className="mx-auto mb-2" />
                                <h2 className="text-xl font-semibold">Campaigns</h2>
                                <p className="text-neutral-500">View all campaigns</p>
                            </div>
                        </Card>
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
