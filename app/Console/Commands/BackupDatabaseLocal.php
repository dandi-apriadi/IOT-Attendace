<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabaseLocal extends Command
{
    protected $signature = 'backup:database-local {--keep=7 : Number of latest backup files to keep}';

    protected $description = 'Create SQL backup for local network deployment';

    public function handle(): int
    {
        $host = (string) config('database.connections.mysql.host');
        $port = (string) config('database.connections.mysql.port');
        $database = (string) config('database.connections.mysql.database');
        $username = (string) config('database.connections.mysql.username');
        $password = (string) config('database.connections.mysql.password');

        if ($database === '' || $username === '') {
            $this->error('Database configuration is incomplete.');
            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $fileName = 'db_backup_' . now()->format('Ymd_His') . '.sql';
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

        $command = [
            'mysqldump',
            '--host=' . $host,
            '--port=' . $port,
            '--user=' . $username,
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            $database,
        ];

        if ($password !== '') {
            $command[] = '--password=' . $password;
        }

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Backup failed. Ensure mysqldump is installed and available in PATH.');
            $this->line(trim($process->getErrorOutput()));
            return self::FAILURE;
        }

        File::put($filePath, $process->getOutput());

        $this->cleanupOldBackups($backupDir, (int) $this->option('keep'));

        $this->info('Backup completed: ' . $filePath);
        return self::SUCCESS;
    }

    private function cleanupOldBackups(string $backupDir, int $keep): void
    {
        $keep = max(1, $keep);
        $files = collect(File::files($backupDir))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.sql'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        $files->slice($keep)->each(function ($file): void {
            File::delete($file->getPathname());
        });
    }
}
