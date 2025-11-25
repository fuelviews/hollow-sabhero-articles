<?php

namespace Fuelviews\SabHeroArticles\Filament\Resources;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextEntry;
use Fuelviews\SabHeroArticles\Filament\Resources\CategoryResource\Pages\EditCategory;
use Fuelviews\SabHeroArticles\Filament\Resources\CategoryResource\Pages\ListCategories;
use Fuelviews\SabHeroArticles\Filament\Resources\CategoryResource\Pages\ViewCategory;
use Fuelviews\SabHeroArticles\Filament\Resources\CategoryResource\RelationManagers\PostsRelationManager;
use Fuelviews\SabHeroArticles\Models\Category;
use Illuminate\Support\Str;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-squares-plus';

    protected static UnitEnum|string|null $navigationGroup = 'Articles';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) Category::count();
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->sortable(),

                Tables\Columns\TextColumn::make('posts_count')
                    ->badge()
                    ->label('Posts Count')
                    ->counts('posts')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone('America/New_York')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone('America/New_York')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    ReplicateAction::make()
                        ->color('info')
                        ->excludeAttributes(['posts_count'])
                        ->beforeReplicaSaved(function ($replica): void {
                            $replica->name = $replica->name . ' (Copy)';
                            $replica->slug = Str::slug($replica->name . ' copy ' . time());
                        })
                        ->successNotificationTitle('Category copied successfully'),
                    DeleteAction::make(),
                ])->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PostsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            // 'edit' => EditCategory::route('/{record}/edit'),
            'view' => ViewCategory::route('/{record}'),
        ];
    }
}
