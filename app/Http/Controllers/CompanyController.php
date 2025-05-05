<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyController extends Controller
{
    /**
     * Display a listing of the companies.
     */
    public function index(Request $request)
    {
        $query = Company::withCount('contacts');
        
        // Apply company name search filter if provided
        if ($request->has('search') && !empty($request->search)) {
            $query->where('company_name', 'LIKE', '%' . $request->search . '%');
        }
        
        // Apply city filter if provided
        if ($request->has('city') && !empty($request->city) && $request->city !== 'all') {
            $query->where('city_or_region', $request->city);
        }
        
        // Apply state filter if provided
        if ($request->has('state') && !empty($request->state) && $request->state !== 'all') {
            $query->where('state', $request->state);
        }
        
        $companies = $query->get();
        
        // Get unique cities and states for filter dropdowns
        $cities = Company::distinct('city_or_region')
            ->whereNotNull('city_or_region')
            ->pluck('city_or_region')
            ->sort()
            ->values();
            
        $states = Company::distinct('state')
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
        ]);
    }
}