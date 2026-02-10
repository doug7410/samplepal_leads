import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import WysiwygEditor from '@/components/wysiwyg-editor';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Company, type Contact } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, GripVertical, Plus, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface SequenceCreateProps {
    companies: Company[];
    contacts: Contact[];
}

interface StepData {
    name: string;
    subject: string;
    content: string;
    delay_days: number;
    send_time: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Sequences', href: '/sequences' },
    { title: 'Create Sequence', href: '/sequences/create' },
];

export default function SequenceCreate({ companies, contacts }: SequenceCreateProps) {
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [steps, setSteps] = useState<StepData[]>([{ name: 'Step 1', subject: '', content: '', delay_days: 0, send_time: '' }]);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [activeStep, setActiveStep] = useState(0);

    const addStep = () => {
        const newSteps = [
            ...steps,
            {
                name: `Step ${steps.length + 1}`,
                subject: '',
                content: '',
                delay_days: steps.length > 0 ? 1 : 0,
                send_time: '',
            },
        ];
        setSteps(newSteps);
        setActiveStep(newSteps.length - 1);
    };

    const removeStep = (index: number) => {
        if (steps.length <= 1) return;
        const newSteps = steps.filter((_, i) => i !== index);
        setSteps(newSteps);
        if (activeStep >= newSteps.length) {
            setActiveStep(newSteps.length - 1);
        }
    };

    const updateStep = (index: number, field: keyof StepData, value: string | number) => {
        const newSteps = [...steps];
        newSteps[index] = { ...newSteps[index], [field]: value };
        setSteps(newSteps);
    };

    const moveStep = (index: number, direction: 'up' | 'down') => {
        if (direction === 'up' && index === 0) return;
        if (direction === 'down' && index === steps.length - 1) return;

        const newSteps = [...steps];
        const targetIndex = direction === 'up' ? index - 1 : index + 1;
        [newSteps[index], newSteps[targetIndex]] = [newSteps[targetIndex], newSteps[index]];

        newSteps.forEach((step, i) => {
            step.name = `Step ${i + 1}`;
        });

        setSteps(newSteps);
        setActiveStep(targetIndex);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route('sequences.store'),
            {
                name,
                description,
                steps,
                entry_filter: null,
            } as any,
            {
                onFinish: () => setProcessing(false),
                onError: (errors) => setErrors(errors),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Sequence" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild className="mr-2">
                            <Link href={route('sequences.index')}>
                                <ChevronLeft size={16} />
                                <span>Back to Sequences</span>
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-bold">Create Sequence</h1>
                    </div>

                    <Button onClick={handleSubmit} disabled={processing} className="flex items-center gap-1">
                        <Save size={16} />
                        <span>Save Sequence</span>
                    </Button>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                        <div className="space-y-4 lg:col-span-2">
                            <Card className="p-5">
                                <h2 className="mb-4 text-lg font-semibold">Sequence Details</h2>

                                <div className="space-y-4">
                                    <div>
                                        <Label htmlFor="name">Sequence Name</Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            value={name}
                                            onChange={(e) => setName(e.target.value)}
                                            placeholder="E.g., Welcome Series, Follow-up Sequence"
                                            className={errors.name ? 'border-red-500' : ''}
                                        />
                                        {errors.name && <div className="mt-1 text-sm text-red-500">{errors.name}</div>}
                                    </div>

                                    <div>
                                        <Label htmlFor="description">Description (Optional)</Label>
                                        <Textarea
                                            id="description"
                                            value={description}
                                            onChange={(e) => setDescription(e.target.value)}
                                            placeholder="Brief description of this sequence's purpose"
                                            rows={2}
                                        />
                                    </div>
                                </div>
                            </Card>

                            <Card className="p-5">
                                <div className="mb-4 flex items-center justify-between">
                                    <h2 className="text-lg font-semibold">Email Steps</h2>
                                    <Button type="button" variant="outline" size="sm" onClick={addStep}>
                                        <Plus size={14} className="mr-1" />
                                        Add Step
                                    </Button>
                                </div>

                                <div className="flex gap-4">
                                    <div className="w-48 flex-shrink-0 space-y-2">
                                        {steps.map((step, index) => (
                                            <div
                                                key={index}
                                                className={`cursor-pointer rounded-lg border p-3 transition-colors ${
                                                    activeStep === index
                                                        ? 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20'
                                                        : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                                                }`}
                                                onClick={() => setActiveStep(index)}
                                            >
                                                <div className="flex items-center gap-2">
                                                    <GripVertical size={14} className="text-gray-400" />
                                                    <span className="text-sm font-medium">{step.name}</span>
                                                </div>
                                                <div className="mt-1 ml-6 text-xs text-gray-500">
                                                    {index === 0 ? 'Immediately' : `+${step.delay_days} day${step.delay_days !== 1 ? 's' : ''}`}
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    <div className="flex-1 rounded-lg border p-4">
                                        {steps[activeStep] && (
                                            <div className="space-y-4">
                                                <div className="flex items-start justify-between">
                                                    <h3 className="font-medium">{steps[activeStep].name}</h3>
                                                    <div className="flex gap-2">
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => moveStep(activeStep, 'up')}
                                                            disabled={activeStep === 0}
                                                        >
                                                            Move Up
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => moveStep(activeStep, 'down')}
                                                            disabled={activeStep === steps.length - 1}
                                                        >
                                                            Move Down
                                                        </Button>
                                                        {steps.length > 1 && (
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => removeStep(activeStep)}
                                                                className="text-red-600 hover:text-red-700"
                                                            >
                                                                <Trash2 size={14} />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-2 gap-4">
                                                    <div>
                                                        <Label>Delay (days after previous step)</Label>
                                                        <Input
                                                            type="number"
                                                            min="0"
                                                            value={steps[activeStep].delay_days}
                                                            onChange={(e) => updateStep(activeStep, 'delay_days', parseInt(e.target.value) || 0)}
                                                            disabled={activeStep === 0}
                                                        />
                                                        {activeStep === 0 && (
                                                            <p className="mt-1 text-xs text-gray-500">First step sends immediately on enrollment</p>
                                                        )}
                                                    </div>
                                                    <div>
                                                        <Label>Preferred Send Time (Optional)</Label>
                                                        <Input
                                                            type="time"
                                                            value={steps[activeStep].send_time}
                                                            onChange={(e) => updateStep(activeStep, 'send_time', e.target.value)}
                                                        />
                                                    </div>
                                                </div>

                                                <div>
                                                    <Label>Subject Line</Label>
                                                    <Input
                                                        type="text"
                                                        value={steps[activeStep].subject}
                                                        onChange={(e) => updateStep(activeStep, 'subject', e.target.value)}
                                                        placeholder="Enter email subject"
                                                    />
                                                </div>

                                                <div>
                                                    <Label>Email Content</Label>
                                                    <WysiwygEditor
                                                        value={steps[activeStep].content}
                                                        onChange={(content) => updateStep(activeStep, 'content', content)}
                                                        placeholder="Write your email content here..."
                                                    />
                                                    <div className="mt-1 text-xs text-gray-500">
                                                        Available variables: <code>{'{{first_name}}'}</code>, <code>{'{{last_name}}'}</code>,{' '}
                                                        <code>{'{{full_name}}'}</code>, <code>{'{{email}}'}</code>, <code>{'{{company}}'}</code>,{' '}
                                                        <code>{'{{job_title}}'}</code>
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </Card>
                        </div>

                        <div className="space-y-4">
                            <Card className="p-5">
                                <h2 className="mb-4 text-lg font-semibold">Sequence Summary</h2>
                                <div className="space-y-3 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Total Steps</span>
                                        <span className="font-medium">{steps.length}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Total Duration</span>
                                        <span className="font-medium">{steps.reduce((acc, step) => acc + step.delay_days, 0)} days</span>
                                    </div>
                                </div>

                                <div className="mt-4 border-t pt-4">
                                    <h3 className="mb-2 font-medium">Timeline Preview</h3>
                                    <div className="space-y-2">
                                        {steps.map((step, index) => {
                                            const daysSoFar = steps.slice(0, index + 1).reduce((acc, s) => acc + s.delay_days, 0);
                                            return (
                                                <div key={index} className="flex items-center gap-2 text-xs">
                                                    <span className="w-16 text-gray-500">{index === 0 ? 'Day 0' : `Day ${daysSoFar}`}</span>
                                                    <span className={activeStep === index ? 'font-medium text-blue-600' : ''}>{step.name}</span>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            </Card>

                            <Card className="p-5">
                                <h2 className="mb-4 text-lg font-semibold">Exit Conditions</h2>
                                <p className="mb-4 text-sm text-gray-600">Contacts will automatically exit the sequence when:</p>
                                <ul className="space-y-2 text-sm">
                                    <li className="flex items-center gap-2">
                                        <span className="h-2 w-2 rounded-full bg-green-500"></span>
                                        They become a customer (deal closed won)
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <span className="h-2 w-2 rounded-full bg-red-500"></span>
                                        They unsubscribe from emails
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <span className="h-2 w-2 rounded-full bg-blue-500"></span>
                                        They complete all steps in the sequence
                                    </li>
                                </ul>
                            </Card>

                            <Button type="submit" disabled={processing} className="w-full">
                                Create Sequence
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
