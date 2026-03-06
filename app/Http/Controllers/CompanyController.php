<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    /**
     * Display a listing of the companies.
     */
    public function index(Request $request)
    {
        $query = Company::withTrashed()->withCount('contacts');

        // Apply company name search filter if provided - case insensitive
        if ($request->has('search') && ! empty($request->search)) {
            $searchTerm = strtolower($request->search);
            $query->whereRaw('LOWER(company_name) LIKE ?', ['%'.$searchTerm.'%']);
        }

        // Apply city filter if provided
        if ($request->has('city') && ! empty($request->city) && $request->city !== 'all') {
            $query->where('city_or_region', $request->city);
        }

        // Apply state filter if provided
        if ($request->has('state') && ! empty($request->state) && $request->state !== 'all') {
            $query->where('state', $request->state);
        }

        // Apply sorting
        $sortField = $request->input('sort', 'company_name');
        $sortDirection = $request->input('direction', 'asc');

        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['company_name', 'city_or_region', 'contacts_count'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        } else {
            // Default sorting
            $query->orderBy('company_name', 'asc');
        }

        // Paginate the results - 10 per page or use a configurable value
        $perPage = $request->input('per_page', 10);
        $companies = $query->paginate($perPage)->withQueryString();

        // Get unique cities and states for filter dropdowns
        $cities = Company::withTrashed()->distinct('city_or_region')
            ->whereNotNull('city_or_region')
            ->pluck('city_or_region')
            ->sort()
            ->values();

        $states = Company::withTrashed()->distinct('state')
            ->whereNotNull('state')
            ->pluck('state')
            ->sort()
            ->values();

        return Inertia::render('companies/index', [
            'companies' => $companies,
            'filters' => [
                'search' => $request->search ?? '',
                'city' => $request->city ?? '',
                'state' => $request->state ?? '',
            ],
            'filterOptions' => [
                'cities' => $cities,
                'states' => $states,
            ],
            'sort' => [
                'field' => $sortField,
                'direction' => $sortDirection,
            ],
        ]);
    }

    /**
     * Show the form for creating a new company.
     */
    public function create(): Response
    {
        return Inertia::render('companies/create');
    }

    /**
     * Store a newly created company in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city_or_region' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
        ]);

        $company = Company::create($validated);

        return redirect()->route('companies.index')->with('success', 'Company created successfully.');
    }

    /**
     * Remove the specified company from storage (soft delete).
     */
    public function destroy(Company $company): RedirectResponse
    {
        $company->delete();

        return redirect()->back();
    }
}
