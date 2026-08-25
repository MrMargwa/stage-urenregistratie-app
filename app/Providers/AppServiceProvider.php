<?php

namespace App\Providers;

use App\Models\TimeEntry;
use App\Services\WorkbookService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $workbooks = app(WorkbookService::class);

        TimeEntry::saved(fn (TimeEntry $entry) => $workbooks->refreshQuietly($entry->user));
        TimeEntry::deleted(fn (TimeEntry $entry) => $workbooks->refreshQuietly($entry->user));
    }
}
