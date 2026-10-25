<?php

use App\Models\EarlyWarning;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('news:sync-bmkg-earthquakes')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->when(fn (): bool => (int) config('services.news.sync_author_id') > 0);

Schedule::command('news:sync-bmkg-weather-alerts')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->when(fn (): bool => (int) config('services.news.sync_author_id') > 0
        || EarlyWarning::query()
            ->where('source', 'BMKG - Peringatan Dini Cuaca')
            ->whereNotNull('created_by')
            ->exists());
