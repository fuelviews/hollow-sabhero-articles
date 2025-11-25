<?php

namespace Fuelviews\SabHeroArticles;

use Fuelviews\SabHeroArticles\Commands\UpgradeV2Command;
use Fuelviews\SabHeroArticles\Components\Breadcrumb;
use Fuelviews\SabHeroArticles\Components\Card;
use Fuelviews\SabHeroArticles\Components\FeatureCard;
use Fuelviews\SabHeroArticles\Components\HeaderCategory;
use Fuelviews\SabHeroArticles\Components\Layout;
use Fuelviews\SabHeroArticles\Components\Markdown;
use Fuelviews\SabHeroArticles\Components\RecentPost;
use Fuelviews\SabHeroArticles\Http\Livewire\SearchAutocomplete;
use Fuelviews\SabHeroArticles\Models\Page;
use Fuelviews\SabHeroArticles\Models\Post;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Spatie\Feed\FeedServiceProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SabHeroArticlesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('sabhero-articles')
            ->hasConfigFile([
                'sabhero-articles',
                'feed',
            ])
            ->hasCommands([
                UpgradeV2Command::class,
            ])
            ->hasMigrations([
                'add_author_fields_to_users_table',
                'create_articles_tables',
                'create_media_table',
                'create_imports_table',
                'create_exports_table',
                'create_failed_import_rows_table',
                'create_pages_table',
                'rename_feature_image_alt_text_column',
                'rename_media_collection_names',
                'drop_feature_image_columns',
                'rename_page_slug_to_route',
                'make_post_feature_image_alt_text_nullable',
            ])

            ->hasViewComponents(
                'sabhero-articles',
                Layout::class,
                RecentPost::class,
                HeaderCategory::class,
                FeatureCard::class,
                Card::class,
                Markdown::class,
                Breadcrumb::class,
            )
            ->hasViews('sabhero-articles')
            ->hasRoutes([
                'web',
                'breadcrumbs',
            ]);
        // $this->loadTestingMigration();
    }

    public function register()
    {
        Paginator::defaultView('sabhero-articles::pagination.tailwind');

        Route::bind('post', function ($value) {
            return Post::where('slug', $value)
                ->published()
                ->with(['user', 'categories', 'tags', 'media'])
                ->firstOrFail();
        });

        Route::bind('user', function ($value) {
            $userModel = config('sabhero-articles.user.model');

            return $userModel::where('slug', $value)->firstOrFail();
        });

        View::composer([
            '*',
        ], static function ($view) {
            if (
                request()->route() &&
                in_array(request()->route()->getName(), [
                    'sabhero-articles.post.show',
                ])
            ) {
                $seoPost = request()->route('post');

                $view->with([
                    'seoPost' => $seoPost,
                ]);
            }
        });

        $this->app->register(SabHeroArticlesEventServiceProvider::class);

        return parent::register();
    }

    public function bootingPackage(): void
    {
        View::composer('*', function ($view) {
            $routeName = Route::currentRouteName();

            $seoPage = Page::where('route', $routeName)
                ->first();

            $view->with('seoPage', $seoPage);
        });
    }

    public function packageBooted(): void
    {
        $this->publishes([
            __DIR__ . '/../database/seeders/PageTableSeeder.php' => database_path('seeders/PageTableSeeder.php'),
        ], 'sabhero-articles-seeders');

        // Register FeedServiceProvider after views are registered
        $this->app->register(FeedServiceProvider::class);

        // Register Livewire components if Livewire is available and not in testing
        if (class_exists(\Livewire\Livewire::class) && !$this->app->environment('testing')) {
            Livewire::component('sabhero-articles::search-autocomplete', SearchAutocomplete::class);
        }
    }

    public function loadTestingMigration(): void
    {
        if ($this->app->environment('testing')) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }
}
