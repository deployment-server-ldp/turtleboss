<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('turtleboss:backup', function () {
    $this->info('Backup created: ' . app(\App\Services\BackupService::class)->run());
})->purpose('Create a database backup in storage/app/private/backups');

Artisan::command('turtleboss:remove-demo', function () {
    $n = \App\Models\Product::withTrashed()->where('is_demo', true)->get()->each(fn ($p) => $p->forceDelete())->count();
    $this->info("Removed $n demo product(s).");
})->purpose('Delete all demo products created by DemoSeeder');

Schedule::command('turtleboss:backup')->dailyAt('03:00');
