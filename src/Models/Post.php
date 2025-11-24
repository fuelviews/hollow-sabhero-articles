<?php

namespace Fuelviews\SabHeroArticles\Models;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Set;
use Fuelviews\SabHeroArticles\Enums\PostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements Feedable, HasMedia
{
    use HasSEO;
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'slug',
        'sub_title',
        'body',
        'status',
        'published_at',
        'scheduled_for',
        'post_feature_image_alt_text',
        'user_id',
    ];

    protected array $dates = [
        'scheduled_for',
    ];

    protected $casts = [
        'id' => 'integer',
        'published_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'status' => PostStatus::class,
        'user_id' => 'integer',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, config('sabhero-articles.tables.prefix').'category_'.config('sabhero-articles.tables.prefix').'post');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, config('sabhero-articles.tables.prefix').'post_'.config('sabhero-articles.tables.prefix').'tag');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('sabhero-articles.user.model'), config('sabhero-articles.user.foreign_key'));
    }

    public function isNotPublished(): bool
    {
        return ! $this->isStatusPublished();
    }

    public function scopePublished(Builder $query)
    {
        return $query->where('status', PostStatus::PUBLISHED)->latest('published_at');
    }

    public function scopeScheduled(Builder $query)
    {
        return $query->where('status', PostStatus::SCHEDULED)->latest('scheduled_for');
    }

    public function scopePending(Builder $query)
    {
        return $query->where('status', PostStatus::PENDING)->latest('created_at');
    }

    public function formattedPublishedDate()
    {
        return $this->published_at?->format('d M Y');
    }

    public function isScheduled()
    {
        return $this->status === PostStatus::SCHEDULED;
    }

    public function isStatusPublished()
    {
        return $this->status === PostStatus::PUBLISHED;
    }

    public function relatedPosts($take = 3)
    {
        return $this->whereHas('categories', function ($query) {
            $query->whereIn(config('sabhero-articles.tables.prefix').'categories.id', $this->categories->pluck('id'))
                ->whereNotIn(config('sabhero-articles.tables.prefix').'posts.id', [$this->id]);
        })->published()->with('user')->take($take)->get();
    }

    public function getDynamicSEOData(): SEOData
    {
        return new SEOData(
            title: $this->title,
            description: ($this->sub_title ?? $this->excerptHtml()),
            author: $this->user->name,
            image: $this->getFirstMediaUrl('post_feature_image'),
            locale: app()->getLocale(),
        );
    }

    public function excerptHtml(): string
    {
        $html = Str::markdown($this->body);
        $html = preg_replace('/<h1[^>]*>.*?<\/h1>/', '', $html, 1);
        $plainText = strip_tags($html);

        return Str::limit($plainText, 304);
    }

    public static function getForm(): array
    {
        return [
            Section::make('Article Details')
                ->schema([
                    Fieldset::make('Titles')
                        ->schema([
                            TextInput::make('title')
                                ->live(true)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
                                    'slug',
                                    Str::slug($state)
                                ))
                                ->required()
                                ->unique(config('sabhero-articles.tables.prefix').'posts', 'title', null, 'id')
                                ->maxLength(255),

                            TextInput::make('slug')
                                ->required()
                                ->maxLength(255),

                            Textarea::make('sub_title')
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Select::make('category_id')
                                ->multiple()
                                ->preload()
                                ->createOptionForm(Category::getForm())
                                ->searchable()
                                ->relationship('categories', 'name')
                                ->columnSpanFull(),

                            Select::make('tag_id')
                                ->multiple()
                                ->preload()
                                ->createOptionForm(Tag::getForm())
                                ->searchable()
                                ->relationship('tags', 'name')
                                ->columnSpanFull(),
                        ]),

                    Textarea::make('body')
                        ->label('Content (Markdown)')
                        ->rows(10)
                        ->required()
                        ->helperText('Enter your content in Markdown format')
                        ->columnSpanFull(),

                    Section::make('Featured Image')
                        ->columns(1)
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('post_feature_image')
                                ->disk(config('sabhero-articles.media.disk'))
                                ->getUploadedFileNameForStorageUsing(function (UploadedFile $file): string {
                                    $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                                    $slug = Str::slug(strtolower($baseName), '_');
                                    $randomSuffix = Str::random(6);

                                    return "{$slug}_{$randomSuffix}.{$file->getClientOriginalExtension()}";
                                })
                                ->responsiveImages()
                                ->image()
                                ->collection('post_feature_image')
                                ->label('Featured Image')
                                ->required(),

                            TextInput::make('post_feature_image_alt_text')
                                ->label('Alt Text')
                                ->required(),
                        ])->collapsible(),

                    Fieldset::make('Status')
                        ->schema([
                            ToggleButtons::make('status')
                                ->live()
                                ->inline()
                                ->options(PostStatus::class)
                                ->required(),

                            DateTimePicker::make('scheduled_for')
                                ->visible(function ($get) {
                                    return $get('status') === PostStatus::SCHEDULED->value;
                                })
                                ->required(function ($get) {
                                    return $get('status') === PostStatus::SCHEDULED->value;
                                })
                                ->minDate(now()->addMinutes(5))
                                ->native(false),
                        ]),

                    Select::make(config('sabhero-articles.user.foreign_key'))
                        ->label('Author')
                        ->relationship('user', config('sabhero-articles.user.columns.name'))
                        ->nullable(false)
                        ->default(auth()->id()),

                ]),
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('post_feature_image')
            ->useDisk(config('sabhero-articles.media.disk'));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('post_feature_image')
            ->withResponsiveImages()
            ->performOnCollections('post_feature_image');
    }

    /*public function getPostFeatureImageAttribute(): ?string
    {
        return $this->getFirstMediaUrl('post_feature_image');
    }*/

    public function stripMarkdown($content): string
    {
        $content = preg_replace('/^(#{1,6})\s*/m', '', $content);

        $content = preg_replace('/(\*\*|__)(.*?)\1/', '$2', $content);
        $content = preg_replace('/(\*|_)(.*?)\1/', '$2', $content);

        $content = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $content);

        $content = preg_replace('/!\[(.*?)\]\((.*?)\)/', '', $content);

        $content = preg_replace('/`(.*?)`/', '$1', $content);

        $content = preg_replace('/^>\s*/m', '', $content);

        $content = preg_replace('/^(-{3,}|_{3,}|\*{3,})$/m', '', $content);

        $content = preg_replace('/^(\-|\*|\+)\s+/m', '', $content);

        $content = preg_replace('/^\d+\.\s+/m', '', $content);

        return trim($content);
    }

    public function getTitleAttribute($value): string
    {
        return Str::title($value);
    }

    public function getSubtitleAttribute($value): string
    {
        return Str::title($value);
    }

    public function excerpt(): string
    {
        return Str::limit($this->stripMarkdown($this->body), 304);
    }

    public function getTable(): string
    {
        return config('sabhero-articles.tables.prefix').'posts';
    }

    public static function getFeedItems()
    {
        return static::published()
            ->with(['user', 'categories', 'media'])
            ->latest('published_at')
            ->limit(50)
            ->get();
    }

    public function toFeedItem(): FeedItem
    {
        $siteUrl = config('app.url');
        $articleUrl = $siteUrl.'/'.config('sabhero-articles.route.prefix');

        $link = route('sabhero-articles.post.show', $this);

        $feedItem = FeedItem::create()
            ->id($link)
            ->title($this->title)
            ->summary($this->sub_title ?? $this->excerptHtml())
            ->updated($this->published_at)
            ->link($link)
            ->authorName($this->user->name)
            ->authorEmail($this->user->email ?? '')
            ->category($this->categories->isNotEmpty() ? $this->categories->first()->name : '');

        $media = $this->getFirstMedia('post_feature_image');
        if ($media) {
            $feedItem->enclosure($this->getFirstMediaUrl('post_feature_image'))
                ->enclosureLength($media->size)
                ->enclosureType($media->mime_type);
        }

        return $feedItem;
    }
}
