<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Company;
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
        
        // Filter by company_id if provided
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        
        $contacts = $query->get();
        $companies = Company::orderBy('company_name')->get(['id', 'company_name']);
        
        return Inertia::render('contacts/index', [
            'contacts' => $contacts,
            'companies' => $companies,
            'filters' => [
                'company_id' => $request->company_id,
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
        ]);
    }

    /**
     * Toggle the has_been_contacted status for a contact.
     */
    public function toggleContacted($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->has_been_contacted = !$contact->has_been_contacted;
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
        ]);
        
        $contact->update($validated);
        
        return redirect()->route('contacts.index')
            ->with('success', 'Contact updated successfully.');
    }
}