<?php

namespace App\Modules\Search;

use App\Modules\Search\Contracts\ProvidesSearchDocuments;
use App\Modules\Search\Observers\CatalogSearchObserver;
use App\Modules\Search\Observers\PanditSearchObserver;
use App\Modules\Search\Observers\TempleSearchObserver;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Temple\Models\Temple;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        $this->app->singleton(\App\Modules\Search\Services\SearchNormalizer::class, fn () => new \App\Modules\Search\Services\SearchNormalizer());
        $this->app->singleton(SearchIndexer::class, fn ($app) => new SearchIndexer($app->make(\App\Modules\Search\Services\SearchNormalizer::class)));

        // Bind the search document providers for each module
        $this->app->bind(ProvidesSearchDocuments::class . ':seo_page', fn () => new \App\Modules\Search\Contracts\SeoPageSearchProvider());
        $this->app->bind(ProvidesSearchDocuments::class . ':temple', fn () => new \App\Modules\Search\Contracts\TempleSearchProvider());
        $this->app->bind(ProvidesSearchDocuments::class . ':pandit', fn () => new \App\Modules\Search\Contracts\PanditSearchProvider());
        $this->app->bind(ProvidesSearchDocuments::class . ':puja_service', fn () => new \App\Modules\Search\Contracts\PujaServiceSearchProvider());

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\Search\Console\SearchReindexCommand::class,
                \App\Modules\Search\Console\ExportSearchSchemasCommand::class,
                \App\Modules\Search\Console\SearchIndexWorkCommand::class,
                \App\Modules\Search\Console\SearchVerifyCommand::class,
                \App\Modules\Search\Console\SearchReplayCommand::class,
                \App\Modules\Search\Console\SearchAggregatePatternsCommand::class,
            ]);
        }
    }

    public function bootModule(): void
    {
        $this->loadModuleMigrations();

        // Named rate limiters (avoid the sha1(domain|IP) shared-counter trap
        // documented in runbook §15): each search surface gets its own
        // counter and budget.
        \Illuminate\Support\Facades\RateLimiter::for('search-public', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(120)->by($request->ip());
        });
        \Illuminate\Support\Facades\RateLimiter::for('search-suggest', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(300)->by($request->ip());
        });
        \Illuminate\Support\Facades\RateLimiter::for('search-analytics', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->ip());
        });
        \Illuminate\Support\Facades\RateLimiter::for('search-privileged', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->ip() . '|' . ($request->user()?->getAuthIdentifier() ?? 'anon'));
        });

        // Register model observers for live reindex
        Temple::observe(TempleSearchObserver::class);
        PanditProfile::observe(PanditSearchObserver::class);
        CatalogItem::observe(CatalogSearchObserver::class);
    }
}
