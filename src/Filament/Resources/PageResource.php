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
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Fuelviews\SabHeroArticles\Filament\Resources\PageResource\Pages\CreatePage;
use Fuelviews\SabHeroArticles\Filament\Resources\PageResource\Pages\EditPage;
use Fuelviews\SabHeroArticles\Filament\Resources\PageResource\Pages\ListPages;
use Fuelviews\SabHeroArticles\Filament\Resources\PageResource\Pages\ViewPage;
use Fuelviews\SabHeroArticles\Models\Page;
use Illuminate\Support\Str;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-book-open';

    protected static UnitEnum|string|null $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) Page::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema(Page::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->description(function ($record) {
                        return Str::limit($record->description, 80);
                    })
                    ->searchable()
                    ->sortable()
                    ->limit(80),

                Tables\Columns\TextColumn::make('route')
                    ->label('Route')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\SpatieMediaLibraryImageColumn::make('page_feature_image')
                    ->label('Featured Image')
                    ->collection('page_feature_image')
                    ->circular(),

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
                        ->beforeReplicaSaved(function (Page $replica): void {
                            $replica->title = $replica->title.' (Copy)';
                            $replica->route = Str::slug($replica->title.' copy '.time());
                        })
                        ->afterReplicaSaved(function (Page $replica, Page $original): void {
                            // Copy media/images using Spatie's copyMedia method
                            $mediaItems = $original->getMedia('page_feature_image');
                            foreach ($mediaItems as $media) {
                                $media->copy($replica, 'page_feature_image');
                            }
                        })
                        ->successNotificationTitle('Page copied successfully'),
                    DeleteAction::make(),
                ])->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Page')
                ->schema([
                    TextEntry::make('title'),

                    TextEntry::make('route')
                        ->label('Route'),

                    TextEntry::make('description')
                        ->label('Meta Description')
                        ->formatStateUsing(function ($state) {
                            return ucfirst($state);
                        })
                        ->columnSpanFull(),

                    SpatieMediaLibraryImageEntry::make('page_feature_image')
                        ->label('Featured Image')
                        ->collection('page_feature_image')
                        ->columnSpanFull(),
                ])->columns(2)
                ->icon('heroicon-o-square-3-stack-3d'),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
            'view' => ViewPage::route('/{record}'),
        ];
    }
}
