<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Display a listing of all contacts.
     */
    public function index(Request $request)
    {
        $query = Contact::with('company');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('deal_status')) {
            $query->where('deal_status', $request->deal_status);
        }

        if ($request->filled('job_title')) {
            $query->where('job_title', $request->job_title);
        }

        if ($request->filled('job_title_category')) {
            $query->where('job_title_category', $request->job_title_category);
        }

        $contacts = $query->get();
        $companies = Company::orderBy('company_name')->get(['id', 'company_name']);
        $jobTitles = Contact::whereNotNull('job_title')->where('job_title', '!=', '')->distinct()->orderBy('job_title')->pluck('job_title');
        $jobCategories = Contact::whereNotNull('job_title_category')->where('job_title_category', '!=', '')->distinct()->orderBy('job_title_category')->pluck('job_title_category');

        return Inertia::render('contacts/index', [
            'contacts' => $contacts,
            'companies' => $companies,
            'jobTitles' => $jobTitles,
            'jobCategories' => $jobCategories,
            'filters' => [
                'company_id' => $request->company_id,
                'deal_status' => $request->deal_status,
                'job_title' => $request->job_title,
                'job_title_category' => $request->job_title_category,
            ],
        ]);
    }

    /**
     * Display a form to create a new contact for a company.
     */
    public function create($company_id)
    {
        $company = Company::findOrFail($company_id);

        return Inertia::render('contacts/create', [
            'company_id' => $company_id,
            'company' => $company,
            'errors' => session()->get('errors') ? session()->get('errors')->getBag('default')->getMessages() : (object) [],
        ]);
    }

    /**
     * Toggle the has_been_contacted status for a contact.
     */
    public function toggleContacted($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->has_been_contacted = ! $contact->has_been_contacted;
        $contact->save();

        return redirect()->back();
    }

    /**
     * Store a newly created contact in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'has_been_contacted' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        Contact::create($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Show the form for editing the specified contact.
     */
    public function edit($id)
    {
        $contact = Contact::with('company')->findOrFail($id);
        $companies = Company::orderBy('company_name')->get(['id', 'company_name']);

        return Inertia::render('contacts/edit', [
            'contact' => $contact,
            'companies' => $companies,
            'errors' => session()->get('errors') ? session()->get('errors')->getBag('default')->getMessages() : (object) [],
        ]);
    }

    /**
     * Update the specified contact in storage.
     */
    public function update(Request $request, $id)
    {
        $contact = Contact::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'has_been_contacted' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $contact->update($validated);

        return redirect()->route('contacts.index')
            ->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);
        $companyId = $contact->company_id;

        $contact->delete();

        return redirect()->route('contacts.index', ['company_id' => $companyId])
            ->with('success', 'Contact deleted successfully.');
    }
}
