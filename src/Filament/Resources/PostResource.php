<?php

namespace Fuelviews\SabHeroArticles\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Fuelviews\SabHeroArticles\Actions\PostExportAction;
use Fuelviews\SabHeroArticles\Actions\PostExportMigrationAction;
use Fuelviews\SabHeroArticles\Actions\PostImportAction;
use Fuelviews\SabHeroArticles\Enums\PostStatus;
use Fuelviews\SabHeroArticles\Filament\Resources\PostResource\Pages\CreatePost;
use Fuelviews\SabHeroArticles\Filament\Resources\PostResource\Pages\EditPost;
use Fuelviews\SabHeroArticles\Filament\Resources\PostResource\Pages\ListPosts;
use Fuelviews\SabHeroArticles\Filament\Resources\PostResource\Pages\ViewPost;
use Fuelviews\SabHeroArticles\Filament\Resources\PostResource\Widgets\ArticlePostPublishedChart;
use Fuelviews\SabHeroArticles\Filament\Tables\Columns\UserAvatar;
use Fuelviews\SabHeroArticles\Models\Post;
use Illuminate\Support\Str;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-minus';

    protected static UnitEnum|string|null $navigationGroup = 'Article';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 0;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationBadge(): ?string
    {
        return (string) Post::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema(Post::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->description(function (Post $record) {
                        return Str::limit($record->sub_title, 60);
                    })
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(function ($state) {
                        return $state->getColor();
                    }),

                Tables\Columns\SpatieMediaLibraryImageColumn::make('post_feature_image')
                    ->collection('post_feature_image')
                    ->label('Featured Image'),

                UserAvatar::make('user')
                    ->label('Author')
                    ->sortable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone('America/New_York')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
            ])->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', config('sabhero-articles.user.columns.name'))
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ])
            ->toolbarActions([
                Action::make('import')
                    ->label('Import posts')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('gray')
                    ->form([
                        Forms\Components\FileUpload::make('zip_file')
                            ->label('Zip files only')
                            ->acceptedFileTypes(['application/zip'])
                            ->required(),
                    ])
                    ->action(fn(array $data) => static::importFromZip($data['zip_file']))
                    ->requiresConfirmation(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('export_csv_and_images')
                        ->label('Export posts (csv)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(fn($records) => static::exportToZip($records))
                        ->requiresConfirmation(),
                    BulkAction::make('export_migration')
                        ->label('Export posts (migration)')
                        ->icon('heroicon-o-code-bracket')
                        ->color('info')
                        ->action(fn($records) => static::exportMigration($records))
                        ->requiresConfirmation()
                        ->modalDescription('Export posts as a migration file package that can be copied to another project. Includes migration file, images, and installation instructions.'),
                ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    ReplicateAction::make()
                        ->color('info')
                        ->excludeAttributes(['scheduled_for'])
                        ->beforeReplicaSaved(function (Post $replica, array $data): void {
                            $replica->title = $replica->title . ' (Copy)';
                            $replica->slug = Str::slug($replica->title . ' copy ' . time());
                            $replica->scheduled_for = null;
                        })
                        ->afterReplicaSaved(function (Post $replica, Post $original): void {
                            // Copy categories
                            $replica->categories()->sync($original->categories->pluck('id'));

                            // Copy tags
                            $replica->tags()->sync($original->tags->pluck('id'));

                            // Copy media/images using Spatie's copyMedia method
                            $mediaItems = $original->getMedia('post_feature_image');
                            foreach ($mediaItems as $media) {
                                $media->copy($replica, 'post_feature_image');
                            }
                        })
                        ->successNotificationTitle('Post copied successfully'),
                    DeleteAction::make(),
                ])->iconButton(),
            ]);
    }


    /**
     * Export posts to ZIP file with CSV and images
     *
     * Delegates to PostExportAction for cleaner, testable code.
     */
    public static function exportToZip($records)
    {
        $exportAction = new PostExportAction;

        return $exportAction->execute($records);
    }

    /**
     * Export posts as migration file package
     *
     * Delegates to PostExportMigrationAction for cleaner, testable code.
     */
    public static function exportMigration($records)
    {
        $exportAction = new PostExportMigrationAction;

        return $exportAction->execute($records);
    }

    /**
     * Import posts from ZIP file containing CSV and images
     *
     * Delegates to PostImportAction for cleaner, testable code.
     */
    public static function importFromZip($zipFile)
    {
        $importAction = new PostImportAction;
        $importAction->execute($zipFile);
    }

    public static function getRecordTitle($record): string
    {
        return ucwords($record->title);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewPost::class,
            EditPost::class,
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ArticlePostPublishedChart::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
            'view' => ViewPost::route('/{record}'),
        ];
    }
}
