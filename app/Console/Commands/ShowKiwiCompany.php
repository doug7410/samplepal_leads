<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class ShowKiwiCompany extends Command
{
    protected $signature = 'show:kiwi-company';

    protected $description = 'Show the Kiwi Tech Lab company and its contacts';

    public function handle()
    {
        $company = Company::where('company_name', 'Kiwi Tech Lab')
            ->with('contacts')
            ->first();

        if (! $company) {
            $this->error('Kiwi Tech Lab company not found!');

            return 1;
        }

        $this->info('Company Information:');
        $this->table(
            ['Field', 'Value'],
            collect($company->toArray())
                ->except(['contacts'])
                ->map(fn ($value, $key) => [$key, is_array($value) ? json_encode($value) : $value])
                ->toArray()
        );

        $this->info("\nContacts Information:");
        $this->table(
            ['ID', 'Name', 'Email', 'Job Title', 'Cell Phone', 'Deal Status'],
            $company->contacts->map(fn ($contact) => [
                $contact->id,
                $contact->first_name.' '.$contact->last_name,
                $contact->email,
                $contact->job_title,
                $contact->cell_phone,
                $contact->deal_status,
            ])->toArray()
        );

        return 0;
    }
}
