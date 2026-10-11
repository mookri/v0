<?php

namespace App\Modules\Search;

use App\Modules\Search\Contracts\ProvidesSearchDocuments;
use App\Modules\Search\Observers\CatalogSearchObserver;
use App\Modules\Search\Observers\PanditSearchObserver;
use App\Modules\Search\Observers\TempleSearchObserver;
use App\Modules\Catalog\Contracts\ListsCatalogItems;
use App\Modules\Catalog\Models\CatalogItem;
use App\Modules\Pandit\Contracts\ListsPanditProfiles;
use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Temple\Contracts\ListsTemples;
use App\Modules\Temple\Models\Temple;
use App\Platform\Modules\ModuleServiceProvider as BaseModuleServiceProvider;

final class ModuleServiceProvider extends BaseModuleServiceProvider
{
    public function registerModule(): void
    {
        \Log::info('[Search] registerModule called');
        $this->app->singleton(SearchIndexer::class, fn () => new SearchIndexer());

        // Bind the search document providers for each module
        $this->app->bind(ProvidesSearchDocuments::class . ':seo_page', fn () => new \App\Modules\Search\Contracts\SeoPageSearchProvider());
        $this->app->bind(ProvidesSearchDocuments::class . ':temple', fn () => new \App\Modules\Search\Contracts\TempleSearchProvider($this->app->make(ListsTemples::class)));
        $this->app->bind(ProvidesSearchDocuments::class . ':pandit', fn () => new \App\Modules\Search\Contracts\PanditSearchProvider($this->app->make(ListsPanditProfiles::class)));
        $this->app->bind(ProvidesSearchDocuments::class . ':puja_service', fn () => new \App\Modules\Search\Contracts\PujaServiceSearchProvider($this->app->make(ListsCatalogItems::class)));

        if ($this->app->runningInConsole()) {
            \Log::info('[Search] registering search:reindex command');
            $this->commands([
                \App\Modules\Search\Console\SearchReindexCommand::class,
            ]);
        }
    }

    public function bootModule(): void
    {
        \Log::info('[Search] bootModule called');
        $this->loadModuleMigrations();

        // Register model observers for live reindex
        Temple::observe(TempleSearchObserver::class);
        PanditProfile::observe(PanditSearchObserver::class);
        CatalogItem::observe(CatalogSearchObserver::class);
    }
}
