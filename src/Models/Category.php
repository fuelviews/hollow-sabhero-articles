<?php

namespace Fuelviews\SabHeroArticles\Models;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, config('sabhero-articles.tables.prefix') . 'category_' . config('sabhero-articles.tables.prefix') . 'post');
    }

    public static function getForm(): array
    {
        return [
            TextInput::make('name')
                ->live(true)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $operation, ?string $old, ?string $state) {
                    $set('slug', Str::slug($state));
                })
                ->unique(config('sabhero-articles.tables.prefix') . 'categories', 'name', null, 'id')
                ->required()
                ->maxLength(155),

            TextInput::make('slug')
                ->unique(config('sabhero-articles.tables.prefix') . 'categories', 'slug', null, 'id')
                ->readOnly()
                ->maxLength(255),
        ];
    }

    public function getNameAttribute($value): string
    {
        return Str::title($value);
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = Str::lower($value);
    }

    public function getTable(): string
    {
        return config('sabhero-articles.tables.prefix') . 'categories';
    }
}
