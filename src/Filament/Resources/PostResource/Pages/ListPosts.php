<?php

namespace Fuelviews\SabHeroArticles\Filament\Resources\PostResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Fuelviews\SabHeroArticles\Filament\Resources\PostResource;
use Fuelviews\SabHeroArticles\Filament\Resources\PostResource\Widgets\ArticlePostPublishedChart;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ArticlePostPublishedChart::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'published' => Tab::make('Published')
                ->modifyQueryUsing(function ($query) {
                    $query->published();
                })->icon('heroicon-o-check-badge'),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(function ($query) {
                    $query->pending();
                })
                ->icon('heroicon-o-clock'),
            'scheduled' => Tab::make('Scheduled')
                ->modifyQueryUsing(function ($query) {
                    $query->scheduled();
                })
                ->icon('heroicon-o-calendar-days'),
        ];
    }
}
