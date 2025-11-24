<?php

namespace Fuelviews\SabHeroArticles\Filament\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Fuelviews\SabHeroArticles\Filament\Resources\UserResource\Pages\CreateUser;
use Fuelviews\SabHeroArticles\Filament\Resources\UserResource\Pages\EditUser;
use Fuelviews\SabHeroArticles\Filament\Resources\UserResource\Pages\ListUsers;
use Fuelviews\SabHeroArticles\Filament\Resources\UserResource\Pages\ViewUser;
use Illuminate\Support\Str;
use UnitEnum;

class UserResource extends Resource
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = \App\Models\User::class;

    protected static ?string $recordRouteKeyName = 'id';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::$model::authors()->count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                SchemaSection::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $set('slug', Str::slug($state));
                            }),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->rules(['alpha_dash']),

                        Forms\Components\Toggle::make('is_author')
                            ->label('Is Author')
                            ->helperText('Make this user visible as a article author'),
                    ])->columns(2),

                SchemaSection::make('Author Information')
                    ->schema([
                        Forms\Components\Textarea::make('bio')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('links')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->required(),
                                Forms\Components\TextInput::make('url')
                                    ->url()
                                    ->required(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                SchemaSection::make('Avatar')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('avatar')
                            ->disk(config('sabhero-articles.media.disk'))
                            ->responsiveImages()
                            ->image()
                            ->label('Avatar')
                            ->collection('avatar')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->getStateUsing(fn ($record) => $record->getAuthorAvatarUrl())
                    ->circular(),

                Tables\Columns\IconColumn::make('is_author')
                    ->boolean()
                    ->label('Author')
                    ->sortable(),

                Tables\Columns\TextColumn::make('posts_count')
                    ->counts('posts')
                    ->label('Posts')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_author')
                    ->label('Authors only')
                    ->placeholder('All users')
                    ->trueLabel('Authors only')
                    ->falseLabel('Non-authors only'),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn ($record) => UserResource::getUrl('edit', ['record' => $record->getKey()])),
                DeleteAction::make(),
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
            Section::make('User Details')
                ->schema([
                    Fieldset::make('Basic Information')
                        ->schema([
                            TextEntry::make('name')
                                ->label('Name'),

                            TextEntry::make('email')
                                ->label('Email'),

                            TextEntry::make('slug')
                                ->label('Slug'),

                            TextEntry::make('is_author')
                                ->label('Is Author')
                                ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                        ]),

                    Fieldset::make('Author Information')
                        ->schema([
                            TextEntry::make('bio')
                                ->label('Bio')
                                ->columnSpanFull(),

                        ]),

                    Fieldset::make('Statistics')
                        ->schema([
                            TextEntry::make('posts_count')
                                ->label('Total Posts')
                                ->getStateUsing(fn ($record) => $record->posts()->count()),

                            TextEntry::make('published_posts_count')
                                ->label('Published Posts')
                                ->getStateUsing(fn ($record) => $record->posts()->published()->count()),

                            TextEntry::make('created_at')
                                ->label('Joined')
                                ->dateTime(),

                            TextEntry::make('updated_at')
                                ->label('Last Updated')
                                ->dateTime(),
                        ]),

                    Fieldset::make('Avatar')
                        ->schema([
                            \Filament\Infolists\Components\ImageEntry::make('avatar')
                                ->getStateUsing(fn ($record) => $record->getAuthorAvatarUrl())
                                ->label('')
                                ->height(150)
                                ->width(150),
                        ]),
                ]),
        ]);
    }

    public static function getRecordTitle($record): string
    {
        return $record->name;
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewUser::class,
            EditUser::class,
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
            'view' => ViewUser::route('/{record}'),
        ];
    }
}
