<?php

namespace Fuelviews\SabHeroArticles;

use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\Support\Colors\Color;

class SabHeroArticles implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'sabhero-articles';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            Filament\Resources\CategoryResource::class,
            Filament\Resources\PostResource::class,
            Filament\Resources\TagResource::class,
            // PageResource removed - using App\Filament\Resources\Pages\PageResource instead
            // Filament\Resources\PageResource::class,
            Filament\Resources\UserResource::class,
        ]);

        $panel->colors([
            'cyan' => Color::Cyan,
        ]);

        $panel->navigationItems([
            NavigationItem::make('CRM')
                ->label(config('sabhero-articles.crm.name'))
                ->url(function () {
                    $link = config('sabhero-articles.crm.link');
                    if (! str_starts_with($link, 'http://') && ! str_starts_with($link, 'https://')) {
                        $link = 'https://'.$link;
                    }

                    return $link;
                }, shouldOpenInNewTab: true)
                ->icon('heroicon-o-presentation-chart-line')
                ->sort(1),
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
