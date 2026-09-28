<?php

namespace Tests\Feature;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    private string $backupPath;

    private string $fakeBin;

    private string $originalPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupPath = $this->makeTempDirectory('backups');
        $this->fakeBin = $this->makeTempDirectory('bin');
        $this->originalPath = (string) getenv('PATH');

        $this->writeFakePgDump();

        putenv('PATH='.$this->fakeBin.':'.$this->originalPath);
        $_ENV['PATH'] = $this->fakeBin.':'.$this->originalPath;
        $_SERVER['PATH'] = $this->fakeBin.':'.$this->originalPath;

        config([
            'backup.path' => $this->backupPath,
            'backup.retention_days' => 7,
            'backup.filename_prefix' => 'ct12rounds-',
        ]);
    }

    protected function tearDown(): void
    {
        putenv('PATH='.$this->originalPath);
        unset($_ENV['PATH'], $_SERVER['PATH']);

        parent::tearDown();
    }

    public function test_it_writes_a_dump_and_leaves_no_partial_file(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 03:30:00'));

        $result = app(DatabaseBackupService::class)->run();

        $this->assertSame(
            $this->backupPath.'/ct12rounds-2026-09-28-033000.dump',
            $result['path'],
        );
        $this->assertFileExists($result['path']);
        $this->assertSame('FAKE DUMP', File::get($result['path']));
        $this->assertGreaterThan(0, $result['size']);
        $this->assertSame([], $this->partialFiles());
    }

    public function test_it_passes_connection_settings_to_pg_dump(): void
    {
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => 'db',
            'database.connections.pgsql.port' => '5432',
            'database.connections.pgsql.database' => 'ct12rounds',
            'database.connections.pgsql.username' => 'ct12rounds',
            'database.connections.pgsql.password' => 'super-secret',
        ]);

        app(DatabaseBackupService::class)->run();

        $argv = File::get($this->capturePath('argv'));

        $this->assertStringContainsString('--host=db', $argv);
        $this->assertStringContainsString('--port=5432', $argv);
        $this->assertStringContainsString('--username=ct12rounds', $argv);
        $this->assertStringContainsString('--dbname=ct12rounds', $argv);
        $this->assertStringContainsString('--format=custom', $argv);
        $this->assertStringContainsString('--no-owner', $argv);

        $this->assertStringNotContainsString(
            'super-secret',
            $argv,
            'The password must not reach the process list.',
        );
        $this->assertSame(
            'super-secret',
            File::get($this->capturePath('password')),
            'The password must reach pg_dump through PGPASSWORD.',
        );
    }

    public function test_it_deletes_dumps_older_than_the_retention_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 03:30:00'));

        $expired = $this->createDump('2026-09-01-033000');
        $kept = $this->createDump('2026-09-25-033000');

        $result = app(DatabaseBackupService::class)->run();

        $this->assertSame(1, $result['pruned']);
        $this->assertFileDoesNotExist($expired);
        $this->assertFileExists($kept);
        $this->assertFileExists($result['path']);
    }

    public function test_it_keeps_everything_when_retention_is_disabled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 03:30:00'));
        config(['backup.retention_days' => 0]);

        $old = $this->createDump('2020-01-01-000000');

        $result = app(DatabaseBackupService::class)->run();

        $this->assertSame(0, $result['pruned']);
        $this->assertFileExists($old);
    }

    public function test_it_ignores_files_that_are_not_dumps(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 03:30:00'));

        $foreign = $this->backupPath.'/someone-elses-2020-01-01-000000.dump';
        $notes = $this->backupPath.'/ct12rounds-2020-01-01-000000.sql';
        File::put($foreign, 'x');
        File::put($notes, 'x');

        app(DatabaseBackupService::class)->run();

        $this->assertFileExists($foreign);
        $this->assertFileExists($notes);
    }

    public function test_it_removes_the_partial_file_when_pg_dump_fails(): void
    {
        $this->failDump('dump failed: connection refused');

        try {
            app(DatabaseBackupService::class)->run();

            $this->fail('Expected a RuntimeException.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('pg_dump failed', $exception->getMessage());
            $this->assertStringContainsString('connection refused', $exception->getMessage());
        }

        $this->assertSame([], $this->partialFiles());
        $this->assertSame([], $this->dumpFiles());
    }

    public function test_it_refuses_to_dump_a_non_postgres_connection(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.driver' => 'sqlite',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('only supported for the pgsql driver');

        app(DatabaseBackupService::class)->run();
    }

    public function test_the_command_reports_a_successful_backup(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 03:30:00'));

        $this->artisan('db:backup')
            ->expectsOutputToContain('Database backup completed.')
            ->assertSuccessful();
    }

    public function test_the_command_fails_when_pg_dump_fails(): void
    {
        $this->failDump('no space left on device');

        $this->artisan('db:backup')
            ->expectsOutputToContain('no space left on device')
            ->assertFailed();
    }

    public function test_the_backup_is_scheduled_daily(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'db:backup'));

        $this->assertNotNull($event, 'db:backup is not scheduled.');
        $this->assertSame('30 3 * * *', $event->expression);
    }

    private function writeFakePgDump(): void
    {
        $script = <<<'SH'
            #!/bin/sh
            printf '%s\n' "$@" > '@ARGV@'
            printf '%s' "$PGPASSWORD" > '@PASSWORD@'
            if [ -f '@STDERR@' ]; then
                cat '@STDERR@' >&2
            fi
            for arg in "$@"; do
                case "$arg" in
                    --file=*) printf 'FAKE DUMP' > "${arg#--file=}" ;;
                esac
            done
            if [ -f '@EXIT@' ]; then
                exit "$(cat '@EXIT@')"
            fi
            exit 0
            SH;

        $script = strtr($script, [
            '@ARGV@' => $this->capturePath('argv'),
            '@PASSWORD@' => $this->capturePath('password'),
            '@STDERR@' => $this->capturePath('stderr'),
            '@EXIT@' => $this->capturePath('exit_code'),
        ]);

        File::put($this->fakeBin.'/pg_dump', $script);
        chmod($this->fakeBin.'/pg_dump', 0755);
    }

    /**
     * Makes the fake pg_dump exit with a failure on the next run.
     */
    private function failDump(string $errorOutput): void
    {
        File::put($this->capturePath('stderr'), $errorOutput);
        File::put($this->capturePath('exit_code'), '1');
    }

    private function capturePath(string $name): string
    {
        return $this->fakeBin.'/'.$name;
    }

    private function createDump(string $timestamp): string
    {
        $path = $this->backupPath.'/ct12rounds-'.$timestamp.'.dump';
        File::put($path, 'OLD DUMP');

        return $path;
    }

    /**
     * @return array<int, string>
     */
    private function partialFiles(): array
    {
        return $this->filesWithSuffix('.partial');
    }

    /**
     * @return array<int, string>
     */
    private function dumpFiles(): array
    {
        return $this->filesWithSuffix('.dump');
    }

    /**
     * @return array<int, string>
     */
    private function filesWithSuffix(string $suffix): array
    {
        $matches = File::glob($this->backupPath.'/*'.$suffix);

        return array_map('basename', $matches === false ? [] : $matches);
    }

    private function makeTempDirectory(string $suffix): string
    {
        $path = sys_get_temp_dir().'/ct12rounds-'.$suffix.'-'.bin2hex(random_bytes(6));

        File::ensureDirectoryExists($path);

        return $path;
    }
}
