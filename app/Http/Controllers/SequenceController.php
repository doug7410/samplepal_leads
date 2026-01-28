<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Sequence;
use App\Services\SequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SequenceController extends Controller
{
    protected SequenceService $sequenceService;

    public function __construct(SequenceService $sequenceService)
    {
        $this->sequenceService = $sequenceService;
    }

    public function index(Request $request): Response
    {
        $sequences = Sequence::with(['steps', 'sequenceContacts'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $sequencesWithStats = $sequences->through(function ($sequence) {
            $stats = $this->sequenceService->getStatistics($sequence);

            return array_merge($sequence->toArray(), [
                'stats' => $stats,
            ]);
        });

        return Inertia::render('sequences/index', [
            'sequences' => $sequencesWithStats,
        ]);
    }

    public function create(): Response
    {
        $companies = Company::has('contacts')->orderBy('company_name')->get();
        $contacts = Contact::with('company')
            ->whereNotNull('email')
            ->where('has_unsubscribed', false)
            ->where('deal_status', '!=', 'closed_won')
            ->get();

        return Inertia::render('sequences/create', [
            'companies' => $companies,
            'contacts' => $contacts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'entry_filter' => 'nullable|array',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.subject' => 'required|string|max:255',
            'steps.*.content' => 'required|string',
            'steps.*.delay_days' => 'required|integer|min:0',
            'steps.*.send_time' => 'nullable|date_format:H:i',
        ]);

        $validated['user_id'] = $request->user()->id;

        $sequence = $this->sequenceService->createSequence($validated);

        return redirect()->route('sequences.show', $sequence)
            ->with('success', 'Sequence created successfully.');
    }

    public function show(Sequence $sequence): Response
    {
        $sequence->load(['steps', 'sequenceContacts.contact.company']);

        $statistics = $this->sequenceService->getStatistics($sequence);

        return Inertia::render('sequences/show', [
            'sequence' => $sequence,
            'statistics' => $statistics,
        ]);
    }

    public function edit(Sequence $sequence): Response
    {
        if ($sequence->status === Sequence::STATUS_ACTIVE) {
            return Inertia::render('sequences/show', [
                'sequence' => $sequence->load(['steps', 'sequenceContacts.contact.company']),
                'statistics' => $this->sequenceService->getStatistics($sequence),
                'error' => 'Active sequences cannot be edited. Pause the sequence first.',
            ]);
        }

        $sequence->load('steps');

        $companies = Company::has('contacts')->orderBy('company_name')->get();
        $contacts = Contact::with('company')
            ->whereNotNull('email')
            ->where('has_unsubscribed', false)
            ->where('deal_status', '!=', 'closed_won')
            ->get();

        return Inertia::render('sequences/edit', [
            'sequence' => $sequence,
            'companies' => $companies,
            'contacts' => $contacts,
        ]);
    }

    public function update(Request $request, Sequence $sequence): RedirectResponse
    {
        if ($sequence->status === Sequence::STATUS_ACTIVE) {
            return redirect()->route('sequences.show', $sequence)
                ->with('error', 'Active sequences cannot be edited. Pause the sequence first.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'entry_filter' => 'nullable|array',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.subject' => 'required|string|max:255',
            'steps.*.content' => 'required|string',
            'steps.*.delay_days' => 'required|integer|min:0',
            'steps.*.send_time' => 'nullable|date_format:H:i',
        ]);

        $this->sequenceService->updateSequence($sequence, $validated);

        return redirect()->route('sequences.show', $sequence)
            ->with('success', 'Sequence updated successfully.');
    }

    public function destroy(Sequence $sequence): RedirectResponse
    {
        if ($sequence->status === Sequence::STATUS_ACTIVE) {
            return redirect()->route('sequences.show', $sequence)
                ->with('error', 'Active sequences cannot be deleted. Pause the sequence first.');
        }

        $this->sequenceService->deleteSequence($sequence);

        return redirect()->route('sequences.index')
            ->with('success', 'Sequence deleted successfully.');
    }

    public function activate(Sequence $sequence): RedirectResponse
    {
        if ($sequence->status === Sequence::STATUS_ACTIVE) {
            return redirect()->route('sequences.show', $sequence)
                ->with('error', 'Sequence is already active.');
        }

        $success = $this->sequenceService->activateSequence($sequence);

        return redirect()->route('sequences.show', $sequence)
            ->with($success ? 'success' : 'error',
                $success ? 'Sequence activated successfully.' : 'Failed to activate sequence. Make sure it has at least one step.');
    }

    public function pause(Sequence $sequence): RedirectResponse
    {
        if ($sequence->status !== Sequence::STATUS_ACTIVE) {
            return redirect()->route('sequences.show', $sequence)
                ->with('error', 'Only active sequences can be paused.');
        }

        $success = $this->sequenceService->pauseSequence($sequence);

        return redirect()->route('sequences.show', $sequence)
            ->with($success ? 'success' : 'error',
                $success ? 'Sequence paused successfully.' : 'Failed to pause sequence.');
    }

    public function addContacts(Request $request, Sequence $sequence): RedirectResponse
    {
        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $count = $this->sequenceService->addContactsToSequence($sequence, $validated['contact_ids']);

        return redirect()->route('sequences.show', $sequence)
            ->with('success', "{$count} contacts added to sequence.");
    }

    public function removeContact(Sequence $sequence, Contact $contact): RedirectResponse
    {
        $success = $this->sequenceService->removeContactFromSequence($sequence, $contact->id);

        return redirect()->route('sequences.show', $sequence)
            ->with($success ? 'success' : 'error',
                $success ? 'Contact removed from sequence.' : 'Contact not found in sequence.');
    }
}
