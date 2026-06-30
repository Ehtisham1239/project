<?php

use App\Console\Commands\RunBackupCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Milestone 4: nightly database backup
Schedule::command(RunBackupCommand::class)->dailyAt('02:00');
