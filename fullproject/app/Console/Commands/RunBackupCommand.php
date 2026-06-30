<?php

namespace App\Console\Commands;

use App\Models\Backup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Runs a mysqldump and stores the result on the configured backup disk.
 * Scheduled in routes/console.php via Schedule::command(RunBackupCommand::class)->dailyAt('02:00').
 *
 * Requires mysqldump to be available on the server PATH (already present in the
 * project's Dockerfile via the default-mysql-client apt package).
 */
class RunBackupCommand extends Command
{
    protected $signature = 'backup:run';
    protected $description = 'Create a database backup and store it on the configured disk';

    public function handle(): int
    {
        $filename = 'backup-'.now()->format('Y-m-d_H-i-s').'.sql';
        $path = storage_path('app/backups/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $db = config('database.connections.mysql');

        $process = new Process([
            'mysqldump',
            '-h', $db['host'],
            '-u', $db['username'],
            '-p'.$db['password'],
            $db['database'],
            '--result-file='.$path,
        ]);

        $process->run();

        $success = $process->isSuccessful() && file_exists($path);

        Backup::create([
            'filename' => $filename,
            'disk' => config('filesystems.backup_disk', 'local'),
            'size_bytes' => $success ? filesize($path) : null,
            'status' => $success ? 'completed' : 'failed',
        ]);

        if ($success) {
            Storage::disk(config('filesystems.backup_disk', 'local'))
                ->putFileAs('backups', new \Illuminate\Http\File($path), $filename);
            $this->info("Backup completed: {$filename}");
            return self::SUCCESS;
        }

        $this->error('Backup failed: '.$process->getErrorOutput());
        return self::FAILURE;
    }
}
