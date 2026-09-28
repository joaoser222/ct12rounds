<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    /**
     * Result of a successful run.
     *
     * @return array{path: string, size: int, pruned: int}
     */
    public function run(): array
    {
        $directory = $this->directory();
        File::ensureDirectoryExists($directory);

        $target = $this->targetPath();
        $partial = $target.'.partial';

        try {
            $this->dump($partial);
        } catch (ProcessFailedException $exception) {
            File::delete($partial);

            $process = $exception->getProcess();

            throw new RuntimeException(
                'pg_dump failed: '.trim($process->getErrorOutput() ?: $process->getOutput()),
                previous: $exception,
            );
        }

        // Only a fully written dump earns the .dump extension, so a truncated
        // file can never be mistaken for a restorable backup.
        File::move($partial, $target);

        return [
            'path' => $target,
            'size' => File::size($target),
            'pruned' => $this->prune(),
        ];
    }

    /**
     * Dumps the default connection into the given path using the custom
     * archive format, which restores with a single pg_restore call.
     */
    private function dump(string $path): void
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        $driver = $config['driver'] ?? 'unknown';

        if ($driver !== 'pgsql') {
            throw new RuntimeException(
                "Backups are only supported for the pgsql driver, [{$connection}] uses [{$driver}].",
            );
        }

        $process = new Process([
            'pg_dump',
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--username='.$config['username'],
            '--dbname='.$config['database'],
            '--format=custom',
            '--compress=6',
            '--no-owner',
            '--no-privileges',
            '--file='.$path,
        ], null, [
            // Passing the password through the environment keeps it out of the
            // process list, where any user on the host could read it.
            'PGPASSWORD' => (string) $config['password'],
        ]);

        $process->setTimeout($this->timeout());
        $process->mustRun();
    }

    /**
     * Removes dumps older than the configured retention window.
     */
    private function prune(): int
    {
        $retention = (int) config('backup.retention_days');

        if ($retention <= 0) {
            return 0;
        }

        $cutoff = Carbon::now()->subDays($retention);

        $expired = collect(File::files($this->directory()))
            ->filter(fn ($file) => $this->isBackup($file->getFilename()))
            ->filter(fn ($file) => $this->createdAt($file->getFilename())->lt($cutoff))
            ->each(fn ($file) => File::delete($file->getPathname()));

        return $expired->count();
    }

    private function directory(): string
    {
        return (string) config('backup.path');
    }

    private function targetPath(): string
    {
        $name = config('backup.filename_prefix').Carbon::now()->format('Y-m-d-His').'.dump';

        return $this->directory().DIRECTORY_SEPARATOR.$name;
    }

    private function isBackup(string $filename): bool
    {
        return str_starts_with($filename, (string) config('backup.filename_prefix'))
            && str_ends_with($filename, '.dump');
    }

    private function createdAt(string $filename): Carbon
    {
        return Carbon::createFromFormat('Y-m-d-His', $this->strip($filename));
    }

    private function strip(string $filename): string
    {
        return substr(
            $filename,
            strlen((string) config('backup.filename_prefix')),
            -strlen('.dump'),
        );
    }

    private function timeout(): ?float
    {
        $timeout = (int) config('backup.timeout');

        return $timeout > 0 ? (float) $timeout : null;
    }
}
