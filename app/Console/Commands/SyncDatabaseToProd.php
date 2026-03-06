<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SyncDatabaseToProd extends Command
{
    protected $signature = 'db:sync-to-prod';

    protected $description = 'Sync local PostgreSQL database to production (overwrites prod)';

    public function handle(): int
    {
        if (! $this->confirm('This will OVERWRITE the production database. Are you sure?')) {
            $this->info('Aborted.');

            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_His');

        $local = config('database.connections.pgsql');
        $prod = config('database.connections.prod');

        $localDump = "{$backupDir}/local_{$timestamp}.sql";
        $prodDump = "{$backupDir}/prod_{$timestamp}.sql";

        $this->info('Backing up production database...');
        $this->runPgDump($prod, $prodDump);
        $this->info("Production backup saved to: {$prodDump}");

        $this->info('Backing up local database...');
        $this->runPgDump($local, $localDump);
        $this->info("Local backup saved to: {$localDump}");

        $this->info('Restoring local database to production...');
        $this->runPsqlRestore($prod, $localDump);
        $this->info('Sync complete.');

        return self::SUCCESS;
    }

    private function runPgDump(array $connection, string $outputFile): void
    {
        $process = Process::fromShellCommandline(
            "pg_dump -h {$connection['host']} -p {$connection['port']} -U {$connection['username']} -d {$connection['database']} --clean --no-owner --no-acl -f ".escapeshellarg($outputFile)
        );
        $process->setEnv(['PGPASSWORD' => $connection['password']]);
        $process->setTimeout(300);
        $process->mustRun();
    }

    private function runPsqlRestore(array $connection, string $dumpFile): void
    {
        $process = Process::fromShellCommandline(
            "psql -h {$connection['host']} -p {$connection['port']} -U {$connection['username']} -d {$connection['database']} -f ".escapeshellarg($dumpFile)
        );
        $process->setEnv(['PGPASSWORD' => $connection['password']]);
        $process->setTimeout(300);
        $process->mustRun();
    }
}
