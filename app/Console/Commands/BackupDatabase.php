<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('db:backup')]
#[Description('Dump the PostgreSQL database with pg_dump and prune expired backups')]
class BackupDatabase extends Command
{
    public function handle(DatabaseBackupService $service): int
    {
        try {
            $result = $service->run();
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Database backup completed.');
        $this->components->twoColumnDetail('File', $result['path']);
        $this->components->twoColumnDetail('Size', $this->humanSize($result['size']));
        $this->components->twoColumnDetail('Pruned', (string) $result['pruned']);

        return self::SUCCESS;
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return round($bytes / 1024 ** $power, 2).' '.$units[$power];
    }
}
