import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { type BreadcrumbItem, type Sequence } from '@/types';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
  ArrowUpRight,
  PenLine,
  Pause,
  Play,
  Plus,
  RefreshCw,
  Users,
  Mail,
  CheckCircle,
  XCircle
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';

interface SequencesIndexProps {
  sequences: {
    data: Sequence[];
    links: Array<{
      url: string | null;
      label: string;
      active: boolean;
    }>;
  };
}

const statusBadge = {
  draft: { label: 'Draft', color: 'bg-gray-100 text-gray-800' },
  active: { label: 'Active', color: 'bg-green-100 text-green-800' },
  paused: { label: 'Paused', color: 'bg-yellow-100 text-yellow-800' },
};

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
  {
    title: 'Sequences',
    href: '/sequences',
  },
];

export default function SequencesIndex({ sequences }: SequencesIndexProps) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Drip Sequences" />
      <div className="flex h-full flex-1 flex-col gap-4 p-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Drip Sequences</h1>
          <Button asChild>
            <Link href={route('sequences.create')} className="flex items-center gap-1">
              <Plus size={16} />
              <span>New Sequence</span>
            </Link>
          </Button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          {sequences.data.map((sequence) => (
            <Card key={sequence.id} className="overflow-hidden flex flex-col">
              <div className="p-4 flex-1">
                <div className="flex justify-between items-start mb-2">
                  <Badge className={sequence.status && statusBadge[sequence.status] ? statusBadge[sequence.status].color : 'bg-gray-100 text-gray-800'}>
                    {sequence.status && statusBadge[sequence.status] ? statusBadge[sequence.status].label : 'Unknown'}
                  </Badge>
                  <span className="text-xs text-gray-500">
                    Created {new Date(sequence.created_at).toLocaleDateString()}
                  </span>
                </div>

                <h3 className="text-lg font-semibold mb-1">{sequence.name}</h3>

                {sequence.description && (
                  <p className="text-sm text-gray-700 mb-3 line-clamp-2">
                    {sequence.description}
                  </p>
                )}

                <div className="space-y-2 text-sm">
                  <div className="flex items-center gap-1">
                    <Mail size={14} className="text-gray-500" />
                    <span className="text-gray-700">
                      {sequence.steps?.length || 0} steps
                    </span>
                  </div>

                  {sequence.stats && (
                    <>
                      <div className="flex items-center gap-1">
                        <Users size={14} className="text-gray-500" />
                        <span className="text-gray-700">
                          {sequence.stats.total_enrolled} enrolled ({sequence.stats.active} active)
                        </span>
                      </div>

                      <div className="flex items-center gap-4">
                        <div className="flex items-center gap-1">
                          <CheckCircle size={14} className="text-green-500" />
                          <span className="text-gray-700">{sequence.stats.completed} completed</span>
                        </div>
                        <div className="flex items-center gap-1">
                          <XCircle size={14} className="text-red-500" />
                          <span className="text-gray-700">{sequence.stats.exited} exited</span>
                        </div>
                      </div>
                    </>
                  )}
                </div>
              </div>

              <div className="bg-gray-50 dark:bg-gray-800 p-3 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                <div className="flex gap-2">
                  <Button size="sm" variant="ghost" asChild>
                    <Link href={route('sequences.show', { sequence: sequence.id })} className="flex items-center gap-1">
                      <ArrowUpRight size={14} />
                      <span>View</span>
                    </Link>
                  </Button>

                  {sequence.status !== 'active' && (
                    <Button size="sm" variant="ghost" asChild>
                      <Link href={route('sequences.edit', { sequence: sequence.id })} className="flex items-center gap-1">
                        <PenLine size={14} />
                        <span>Edit</span>
                      </Link>
                    </Button>
                  )}
                </div>

                <div className="flex gap-2">
                  {sequence.status === 'draft' && (
                    <Button
                      size="sm"
                      onClick={() => router.post(route('sequences.activate', { sequence: sequence.id }))}
                      className="flex items-center gap-1"
                    >
                      <Play size={14} />
                      <span>Activate</span>
                    </Button>
                  )}

                  {sequence.status === 'active' && (
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => router.post(route('sequences.pause', { sequence: sequence.id }))}
                      className="flex items-center gap-1"
                    >
                      <Pause size={14} />
                      <span>Pause</span>
                    </Button>
                  )}

                  {sequence.status === 'paused' && (
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => router.post(route('sequences.activate', { sequence: sequence.id }))}
                      className="flex items-center gap-1"
                    >
                      <Play size={14} />
                      <span>Resume</span>
                    </Button>
                  )}
                </div>
              </div>
            </Card>
          ))}

          {sequences.data.length === 0 && (
            <Card className="col-span-full p-8 text-center">
              <RefreshCw size={48} className="mx-auto mb-4 text-gray-300" />
              <h3 className="text-lg font-medium mb-2">No sequences found</h3>
              <p className="text-gray-500 mb-4">
                Get started by creating your first drip sequence.
              </p>
              <Button asChild>
                <Link href={route('sequences.create')}>
                  Create Sequence
                </Link>
              </Button>
            </Card>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
