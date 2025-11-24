<?php

namespace Fuelviews\SabHeroArticles\Models;

use Database\Factories\PageFactory;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Page extends Model implements HasMedia
{
    use HasFactory;
    use HasSEO;
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'route',
        'description',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    protected static function newFactory(): PageFactory
    {
        return new PageFactory;
    }

    public function getDynamicSEOData(): SEOData
    {
        return new SEOData(
            title: ucfirst($this->title ?? ''),
            description: ucfirst($this->description ?? ''),
            image: $this->getFirstMediaUrl('page_feature_image'),
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('page_feature_image')
            ->useDisk(config('sabhero-articles.media.disk'));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('page_feature_image')
            ->withResponsiveImages()
            ->performOnCollections('page_feature_image');
    }

    public static function getForm(): array
    {
        return [
            Section::make('SEO Metadata')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->required(),

                    TextInput::make('route')
                        ->label('Route')
                        ->required()
                        ->unique(__CLASS__, ignoreRecord: true)
                        ->formatStateUsing(fn ($state) => Str::lower($state)),

                    Textarea::make('description')
                        ->label('Meta Description')
                        ->required()
                        ->columnSpanFull(),

                    SpatieMediaLibraryFileUpload::make('page_feature_image')
                        ->disk(config('sabhero-articles.media.disk'))
                        ->label('Featured Image')
                        ->collection('page_feature_image')
                        ->image()
                        ->responsiveImages()
                        ->columnSpanFull(),
                ]),
        ];
    }
}
