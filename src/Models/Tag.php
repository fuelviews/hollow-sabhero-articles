<?php

namespace Fuelviews\SabHeroArticles\Models;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, config('sabhero-articles.tables.prefix') . 'post_' . config('sabhero-articles.tables.prefix') . 'tag');
    }

    public static function getForm(): array
    {
        return [
            TextInput::make('name')
                ->live(true)->afterStateUpdated(fn(Set $set, ?string $state) => $set(
                    'slug',
                    Str::slug($state)
                ))
                ->unique(config('sabhero-articles.tables.prefix') . 'tags', 'name', null, 'id')
                ->required()
                ->maxLength(50),

            TextInput::make('slug')
                ->unique(config('sabhero-articles.tables.prefix') . 'tags', 'slug', null, 'id')
                ->readOnly()
                ->maxLength(155),
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
        return config('sabhero-articles.tables.prefix') . 'tags';
    }
}
