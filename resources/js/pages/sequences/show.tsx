import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Company, type Contact, type Sequence, type SequenceStats } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle, ChevronLeft, Clock, Pause, PenLine, Play, Trash2, UserPlus, Users, XCircle } from 'lucide-react';
import { useState } from 'react';

interface SequenceShowProps {
    sequence: Sequence;
    statistics: SequenceStats;
    contacts?: Contact[];
    companies?: Company[];
}

const statusBadge = {
    draft: { label: 'Draft', color: 'bg-gray-100 text-gray-800' },
    active: { label: 'Active', color: 'bg-green-100 text-green-800' },
    paused: { label: 'Paused', color: 'bg-yellow-100 text-yellow-800' },
};

const contactStatusBadge = {
    active: { label: 'Active', color: 'bg-blue-100 text-blue-800' },
    completed: { label: 'Completed', color: 'bg-green-100 text-green-800' },
    exited: { label: 'Exited', color: 'bg-red-100 text-red-800' },
};

export default function SequenceShow({ sequence, statistics }: SequenceShowProps) {
    const [showAddContacts, setShowAddContacts] = useState(false);
    const [selectedContacts, setSelectedContacts] = useState<number[]>([]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Sequences', href: '/sequences' },
        { title: sequence.name, href: `/sequences/${sequence.id}` },
    ];

    const handleAddContacts = () => {
        if (selectedContacts.length === 0) return;
        router.post(
            route('sequences.add-contacts', { sequence: sequence.id }),
            {
                contact_ids: selectedContacts,
            },
            {
                onSuccess: () => {
                    setShowAddContacts(false);
                    setSelectedContacts([]);
                },
            },
        );
    };

    const handleRemoveContact = (contactId: number) => {
        if (confirm('Are you sure you want to remove this contact from the sequence?')) {
            router.delete(route('sequences.remove-contact', { sequence: sequence.id, contact: contactId }));
        }
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this sequence? This action cannot be undone.')) {
            router.delete(route('sequences.destroy', { sequence: sequence.id }));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={sequence.name} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild className="mr-2">
                            <Link href={route('sequences.index')}>
                                <ChevronLeft size={16} />
                                <span>Back to Sequences</span>
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-bold">{sequence.name}</h1>
                        <Badge
                            className={
                                sequence.status && statusBadge[sequence.status] ? statusBadge[sequence.status].color : 'bg-gray-100 text-gray-800'
                            }
                        >
                            {sequence.status && statusBadge[sequence.status] ? statusBadge[sequence.status].label : 'Unknown'}
                        </Badge>
                    </div>

                    <div className="flex gap-2">
                        {sequence.status === 'draft' && (
                            <>
                                <Button variant="outline" asChild>
                                    <Link href={route('sequences.edit', { sequence: sequence.id })}>
                                        <PenLine size={16} className="mr-1" />
                                        Edit
                                    </Link>
                                </Button>
                                <Button onClick={() => router.post(route('sequences.activate', { sequence: sequence.id }))}>
                                    <Play size={16} className="mr-1" />
                                    Activate
                                </Button>
                            </>
                        )}
                        {sequence.status === 'active' && (
                            <Button variant="outline" onClick={() => router.post(route('sequences.pause', { sequence: sequence.id }))}>
                                <Pause size={16} className="mr-1" />
                                Pause
                            </Button>
                        )}
                        {sequence.status === 'paused' && (
                            <>
                                <Button variant="outline" asChild>
                                    <Link href={route('sequences.edit', { sequence: sequence.id })}>
                                        <PenLine size={16} className="mr-1" />
                                        Edit
                                    </Link>
                                </Button>
                                <Button onClick={() => router.post(route('sequences.activate', { sequence: sequence.id }))}>
                                    <Play size={16} className="mr-1" />
                                    Resume
                                </Button>
                            </>
                        )}
                        {sequence.status !== 'active' && (
                            <Button variant="destructive" onClick={handleDelete}>
                                <Trash2 size={16} className="mr-1" />
                                Delete
                            </Button>
                        )}
                    </div>
                </div>

                {sequence.description && <p className="text-gray-600">{sequence.description}</p>}

                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <Card className="p-4">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-blue-100 p-2">
                                <Users size={20} className="text-blue-600" />
                            </div>
                            <div>
                                <p className="text-sm text-neutral-500">Total Enrolled</p>
                                <p className="text-2xl font-semibold">{statistics.total_enrolled}</p>
                            </div>
                        </div>
                    </Card>

                    <Card className="p-4">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-yellow-100 p-2">
                                <Clock size={20} className="text-yellow-600" />
                            </div>
                            <div>
                                <p className="text-sm text-neutral-500">Active</p>
                                <p className="text-2xl font-semibold">{statistics.active}</p>
                            </div>
                        </div>
                    </Card>

                    <Card className="p-4">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-green-100 p-2">
                                <CheckCircle size={20} className="text-green-600" />
                            </div>
                            <div>
                                <p className="text-sm text-neutral-500">Completed</p>
                                <p className="text-2xl font-semibold">{statistics.completed}</p>
                            </div>
                        </div>
                    </Card>

                    <Card className="p-4">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-red-100 p-2">
                                <XCircle size={20} className="text-red-600" />
                            </div>
                            <div>
                                <p className="text-sm text-neutral-500">Exited</p>
                                <p className="text-2xl font-semibold">{statistics.exited}</p>
                            </div>
                        </div>
                    </Card>
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Card className="p-5">
                        <h2 className="mb-4 text-lg font-semibold">Sequence Steps</h2>
                        <div className="space-y-3">
                            {sequence.steps?.map((step, index) => {
                                const stepStats = statistics.step_stats[step.step_order];
                                return (
                                    <div key={step.id} className="rounded-lg border p-4">
                                        <div className="mb-2 flex items-start justify-between">
                                            <div>
                                                <h3 className="font-medium">{step.name}</h3>
                                                <p className="text-sm text-gray-600">{step.subject}</p>
                                            </div>
                                            <span className="text-xs text-gray-500">
                                                {index === 0 ? 'Immediately' : `+${step.delay_days} day${step.delay_days !== 1 ? 's' : ''}`}
                                            </span>
                                        </div>
                                        {stepStats && (
                                            <div className="mt-2 flex gap-4 text-xs text-gray-600">
                                                <span>Sent: {stepStats.sent}</span>
                                                <span>Opened: {stepStats.opened}</span>
                                                <span>Clicked: {stepStats.clicked}</span>
                                                {stepStats.bounced > 0 && <span className="text-red-600">Bounced: {stepStats.bounced}</span>}
                                                {stepStats.failed > 0 && <span className="text-red-600">Failed: {stepStats.failed}</span>}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </Card>

                    <Card className="p-5">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Exit Reasons</h2>
                        </div>
                        {statistics.exited > 0 ? (
                            <div className="space-y-2">
                                {Object.entries(statistics.exit_reasons).map(([reason, count]) => (
                                    <div key={reason} className="flex items-center justify-between rounded bg-gray-50 p-2">
                                        <span className="capitalize">{reason.replace('_', ' ')}</span>
                                        <span className="font-medium">{count}</span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500">No contacts have exited yet.</p>
                        )}
                    </Card>
                </div>

                <Card className="p-5">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Enrolled Contacts</h2>
                        <Button size="sm" onClick={() => setShowAddContacts(true)}>
                            <UserPlus size={14} className="mr-1" />
                            Add Contacts
                        </Button>
                    </div>

                    {sequence.sequence_contacts && sequence.sequence_contacts.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="px-3 py-2 text-left">Contact</th>
                                        <th className="px-3 py-2 text-left">Company</th>
                                        <th className="px-3 py-2 text-left">Status</th>
                                        <th className="px-3 py-2 text-left">Current Step</th>
                                        <th className="px-3 py-2 text-left">Next Send</th>
                                        <th className="px-3 py-2 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {sequence.sequence_contacts.map((sc) => (
                                        <tr key={sc.id} className="border-b">
                                            <td className="px-3 py-2">
                                                {sc.contact?.first_name} {sc.contact?.last_name}
                                                <div className="text-xs text-gray-500">{sc.contact?.email}</div>
                                            </td>
                                            <td className="px-3 py-2">{sc.contact?.company?.company_name}</td>
                                            <td className="px-3 py-2">
                                                <Badge className={contactStatusBadge[sc.status]?.color || 'bg-gray-100'}>
                                                    {contactStatusBadge[sc.status]?.label || sc.status}
                                                </Badge>
                                                {sc.exit_reason && <span className="ml-2 text-xs text-gray-500">({sc.exit_reason})</span>}
                                            </td>
                                            <td className="px-3 py-2">Step {sc.current_step + 1}</td>
                                            <td className="px-3 py-2">{sc.next_send_at ? new Date(sc.next_send_at).toLocaleString() : '-'}</td>
                                            <td className="px-3 py-2">
                                                {sc.status === 'active' && (
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => handleRemoveContact(sc.contact_id)}
                                                        className="text-red-600 hover:text-red-700"
                                                    >
                                                        <XCircle size={14} />
                                                    </Button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="py-8 text-center text-sm text-gray-500">No contacts enrolled yet. Add contacts to start the sequence.</p>
                    )}
                </Card>

                <Dialog open={showAddContacts} onOpenChange={setShowAddContacts}>
                    <DialogContent className="max-h-[80vh] max-w-2xl overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Add Contacts to Sequence</DialogTitle>
                            <DialogDescription>
                                Select contacts to enroll in this sequence. Customers and unsubscribed contacts are excluded.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="py-4">
                            <p className="mb-4 text-sm text-gray-600">
                                Note: To add contacts, please use the contacts page to select and enroll them via the API or create a filter.
                            </p>
                            <p className="text-sm text-gray-500">This feature will be enhanced in a future update to show a contact picker here.</p>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setShowAddContacts(false)}>
                                Close
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
