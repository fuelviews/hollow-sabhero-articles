<?php

namespace Fuelviews\SabHeroArticles\Filament\Resources\UserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Fuelviews\SabHeroArticles\Filament\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->url(fn($record) => UserResource::getUrl('view', ['record' => $record->getKey()])),
            Actions\DeleteAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            // UserResource::getUrl() => UserResource::getBreadcrumb(),
            // UserResource::getUrl('view', ['record' => $this->getRecord()]) => $this->getRecordTitle(),
        ];
    }
}
