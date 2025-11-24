<?php

namespace Fuelviews\SabHeroArticles\Filament\Resources\CategoryResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Fuelviews\SabHeroArticles\Models\Post;
use Illuminate\Support\Str;

class PostsRelationManager extends RelationManager
{
    protected static string $relationship = 'posts';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema(Post::getForm());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->limit(40)
                    ->description(function (Post $record) {
                        return Str::limit($record->sub_title);
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(function ($state) {
                        return $state->getColor();
                    }),
            ])
            ->filters([
                //
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordActions([
                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ]);
    }
}
