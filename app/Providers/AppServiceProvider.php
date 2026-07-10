<?php

namespace App\Providers;

use App\Services\ProfanityFilter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton so title + content validation share one compiled pattern set.
        $this->app->singleton(
            ProfanityFilter::class,
            fn () => new ProfanityFilter(config('moderation.blacklist', [])),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            // Remap public disk → S3/R2 so all Storage::disk('public') calls use cloud storage.
            Config::set('filesystems.disks.public', Config::get('filesystems.disks.s3'));
        }
    }
}
