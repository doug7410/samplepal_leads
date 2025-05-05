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
    public function index()
    {
        $companies = Company::withCount('contacts')->get();

        return Inertia::render('companies/index', [
            'companies' => $companies,
        ]);
    }
}