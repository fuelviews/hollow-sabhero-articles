<?php

namespace Fuelviews\SabHeroArticles\Filament\Resources\UserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Fuelviews\SabHeroArticles\Filament\Resources\UserResource;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->url(fn($record) => UserResource::getUrl('edit', ['record' => $record->getKey()])),
        ];
    }
}
