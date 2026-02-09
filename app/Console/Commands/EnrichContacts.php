<?php

namespace App\Console\Commands;

use App\Ai\Agents\EmailGuesser;
use App\Ai\Agents\JobCategoryClassifier;
use App\Models\Company;
use App\Models\Contact;
use App\Services\WebsiteValidatorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class EnrichContacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:enrich-contacts
        {--classify-categories : Reclassify all job title categories using AI}
        {--guess-emails : Fill in missing emails using AI pattern analysis}
        {--validate-websites : Check if company websites are reachable}
        {--mark-unusable : Mark contacts at unreachable companies as unusable}
        {--clean-emails : Nullify placeholder and invalid email addresses}
        {--all : Run all enrichment tasks}
        {--dry-run : Show what would change without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enrich contact and company data using AI and validation';

    /** @var array<string, mixed> */
    protected array $dryRunLog = [];

    /**
     * Execute the console command.
     */
    public function handle(WebsiteValidatorService $websiteValidator): int
    {
        $runAll = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be saved');
            $this->newLine();
        }

        $ran = false;

        if ($runAll || $this->option('classify-categories')) {
            $this->classifyCategories($dryRun);
            $ran = true;
        }

        if ($runAll || $this->option('validate-websites')) {
            $this->validateWebsites($websiteValidator, $dryRun);
            $ran = true;
        }

        if ($runAll || $this->option('clean-emails')) {
            $this->cleanEmails($dryRun);
            $ran = true;
        }

        if ($runAll || $this->option('guess-emails')) {
            $this->guessEmails($dryRun);
            $ran = true;
        }

        if ($runAll || $this->option('mark-unusable')) {
            $this->markUnusable($dryRun);
            $ran = true;
        }

        if (! $ran) {
            $this->error('No enrichment task specified. Use --all or a specific option.');

            return self::FAILURE;
        }

        if (! empty($this->dryRunLog)) {
            $prefix = $dryRun ? 'enrichment-dry-run' : 'enrichment-run';
            $path = $prefix.'-'.now()->format('Y-m-d_His').'.json';
            $this->dryRunLog['dry_run'] = $dryRun;
            $this->dryRunLog['ran_at'] = now()->toIso8601String();
            Storage::put($path, json_encode($this->dryRunLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->newLine();
            $this->info('Results saved to: storage/app/private/'.$path);
        }

        return self::SUCCESS;
    }

    protected function classifyCategories(bool $dryRun): void
    {
        $this->info('Classifying job title categories...');

        $contacts = Contact::query()
            ->whereNotNull('job_title')
            ->where('job_title', '!=', '')
            ->select(['id', 'job_title', 'job_title_category'])
            ->get();

        if ($contacts->isEmpty()) {
            $this->warn('No contacts with job titles found.');

            return;
        }

        $this->info("Processing {$contacts->count()} contacts...");

        $changes = [];
        $chunks = $contacts->chunk(20);
        $bar = $this->output->createProgressBar($chunks->count());
        $bar->start();

        foreach ($chunks as $chunk) {
            $payload = $chunk->map(fn ($c) => [
                'id' => $c->id,
                'job_title' => $c->job_title,
            ])->values()->toArray();

            $response = (new JobCategoryClassifier)->prompt(
                'Classify these contacts: '.json_encode($payload)
            );

            foreach ($response['classifications'] as $classification) {
                $contact = $contacts->firstWhere('id', $classification['id']);
                if (! $contact) {
                    continue;
                }

                $newCategory = $classification['category'];
                if ($contact->job_title_category !== $newCategory) {
                    $changes[] = [
                        'id' => $contact->id,
                        'job_title' => $contact->job_title,
                        'old' => $contact->job_title_category ?? '(null)',
                        'new' => $newCategory ?? '(null)',
                    ];

                    if (! $dryRun) {
                        $contact->update(['job_title_category' => $newCategory]);
                    }
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if (empty($changes)) {
            $this->info('No category changes needed.');
        } else {
            $this->info(count($changes).' categories would be changed:');
            $this->table(
                ['ID', 'Job Title', 'Old Category', 'New Category'],
                $changes
            );
        }

        $this->dryRunLog['classify_categories'] = [
            'total_contacts' => $contacts->count(),
            'changes_count' => count($changes),
            'changes' => $changes,
        ];
    }

    protected function validateWebsites(WebsiteValidatorService $validator, bool $dryRun): void
    {
        $this->info('Validating company websites...');

        $companies = Company::query()
            ->whereNotNull('website')
            ->where('website', '!=', '')
            ->get();

        if ($companies->isEmpty()) {
            $this->warn('No companies with websites found.');

            return;
        }

        $this->info("Checking {$companies->count()} websites...");

        $results = [];
        $bar = $this->output->createProgressBar($companies->count());
        $bar->start();

        foreach ($companies as $company) {
            $status = $validator->validate($company->website);

            $results[] = [
                'id' => $company->id,
                'company' => $company->company_name,
                'website' => $company->website,
                'status' => $status,
            ];

            if (! $dryRun) {
                $company->update([
                    'website_status' => $status,
                    'website_checked_at' => now(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['ID', 'Company', 'Website', 'Status'],
            $results
        );

        $statusCounts = collect($results)->countBy('status');
        foreach ($statusCounts as $status => $count) {
            $this->info("  {$status}: {$count}");
        }

        $this->dryRunLog['validate_websites'] = [
            'total_companies' => count($results),
            'status_counts' => $statusCounts->toArray(),
            'results' => $results,
        ];
    }

    protected function cleanEmails(bool $dryRun): void
    {
        $this->info('Cleaning invalid email addresses...');

        $invalidDomains = [
            'example.com',
            'test.com',
            'noemail.com',
            'none.com',
            'placeholder.com',
            'fake.com',
            'invalid.com',
            'email.com',
        ];

        $contacts = Contact::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->filter(function ($contact) use ($invalidDomains) {
                $email = strtolower($contact->email);

                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return true;
                }

                $domain = explode('@', $email)[1] ?? '';

                return in_array($domain, $invalidDomains);
            });

        if ($contacts->isEmpty()) {
            $this->info('No invalid emails found.');

            return;
        }

        $results = $contacts->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->first_name.' '.$c->last_name,
            'email' => $c->email,
        ])->values()->toArray();

        if (! $dryRun) {
            Contact::query()
                ->whereIn('id', $contacts->pluck('id'))
                ->update(['email' => null]);
        }

        $this->info(count($results).' invalid emails '.($dryRun ? 'would be' : '').' nullified:');
        $this->table(['ID', 'Name', 'Old Email'], $results);

        $this->dryRunLog['clean_emails'] = [
            'count' => count($results),
            'contacts' => $results,
        ];
    }

    protected function guessEmails(bool $dryRun): void
    {
        $this->info('Guessing missing emails...');

        $companiesWithMissing = Company::query()
            ->whereHas('contacts', function ($q) {
                $q->whereNull('email')->orWhere('email', '');
            })
            ->with(['contacts' => function ($q) {
                $q->select(['id', 'company_id', 'first_name', 'last_name', 'email']);
            }])
            ->get();

        if ($companiesWithMissing->isEmpty()) {
            $this->warn('No companies with missing emails found.');

            return;
        }

        $guessedResults = [];
        $skippedCompanies = [];
        $bar = $this->output->createProgressBar($companiesWithMissing->count());
        $bar->start();

        foreach ($companiesWithMissing as $company) {
            $contactsWithEmail = $company->contacts->filter(fn ($c) => ! empty($c->email));
            $contactsMissing = $company->contacts->filter(
                fn ($c) => empty($c->email) && ! empty(trim($c->first_name)) && ! empty(trim($c->last_name))
            );

            if ($contactsWithEmail->isEmpty() || $contactsMissing->isEmpty()) {
                $skippedCompanies[] = $company->company_name;
                $bar->advance();

                continue;
            }

            $domains = $contactsWithEmail->map(function ($c) {
                $parts = explode('@', $c->email);

                return $parts[1] ?? null;
            })->filter()->unique()->values();

            if ($domains->isEmpty()) {
                $skippedCompanies[] = $company->company_name;
                $bar->advance();

                continue;
            }

            $domain = $domains->first();

            $payload = json_encode([
                'company_name' => $company->company_name,
                'domain' => $domain,
                'known_emails' => $contactsWithEmail->map(fn ($c) => [
                    'first_name' => $c->first_name,
                    'last_name' => $c->last_name,
                    'email' => $c->email,
                ])->values()->toArray(),
                'missing_emails' => $contactsMissing->map(fn ($c) => [
                    'id' => $c->id,
                    'first_name' => $c->first_name,
                    'last_name' => $c->last_name,
                ])->values()->toArray(),
            ]);

            $response = (new EmailGuesser)->prompt(
                'Analyze email patterns and guess missing emails: '.$payload
            );

            if ($response['confidence'] === 'low') {
                $bar->advance();

                continue;
            }

            foreach ($response['guesses'] as $guess) {
                $contact = $contactsMissing->firstWhere('id', $guess['contact_id']);
                if (! $contact) {
                    continue;
                }

                $guessedDomain = strtolower(explode('@', $guess['guessed_email'])[1] ?? '');
                if ($guessedDomain !== strtolower($domain)) {
                    continue;
                }

                $guessedResults[] = [
                    'id' => $contact->id,
                    'name' => $contact->first_name.' '.$contact->last_name,
                    'company' => $company->company_name,
                    'guessed_email' => $guess['guessed_email'],
                    'pattern' => $response['pattern'],
                    'confidence' => $response['confidence'],
                ];

                if (! $dryRun) {
                    $contact->update([
                        'email' => $guess['guessed_email'],
                        'email_source' => 'ai_guessed',
                    ]);
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if (! empty($skippedCompanies)) {
            $this->warn('Skipped '.count($skippedCompanies).' companies with no reference emails: '.implode(', ', $skippedCompanies));
            $this->newLine();
        }

        if (empty($guessedResults)) {
            $this->info('No emails guessed.');
        } else {
            $this->info(count($guessedResults).' emails guessed:');
            $this->table(
                ['ID', 'Name', 'Company', 'Guessed Email', 'Pattern', 'Confidence'],
                $guessedResults
            );
        }

        $this->dryRunLog['guess_emails'] = [
            'skipped_companies' => $skippedCompanies,
            'guesses_count' => count($guessedResults),
            'guesses' => $guessedResults,
        ];
    }

    protected function markUnusable(bool $dryRun): void
    {
        $this->info('Marking contacts at unreachable companies as unusable...');

        $unreachableCompanyIds = Company::query()
            ->where('website_status', 'unreachable')
            ->pluck('id');

        if ($unreachableCompanyIds->isEmpty()) {
            $this->info('No unreachable companies found. Run --validate-websites first.');

            return;
        }

        $contacts = Contact::query()
            ->whereIn('company_id', $unreachableCompanyIds)
            ->where('is_enrichment_unusable', false)
            ->with('company:id,company_name')
            ->get();

        if ($contacts->isEmpty()) {
            $this->info('No contacts to mark as unusable.');

            return;
        }

        $results = $contacts->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->first_name.' '.$c->last_name,
            'company' => $c->company->company_name,
        ])->toArray();

        if (! $dryRun) {
            Contact::query()
                ->whereIn('company_id', $unreachableCompanyIds)
                ->where('is_enrichment_unusable', false)
                ->update(['is_enrichment_unusable' => true]);
        }

        $this->info(count($results).' contacts marked as unusable:');
        $this->table(['ID', 'Name', 'Company'], $results);

        $this->dryRunLog['mark_unusable'] = [
            'count' => count($results),
            'contacts' => $results,
        ];
    }
}
