@props(['user', 'post' => null, 'size' => 'small', 'showDate' => true])

@php
    $containerClasses = match ($size) {
        'small' => 'gap-2',
        'medium' => 'gap-3',
        'large' => 'gap-4',
        default => 'gap-2'
    };

    $avatarClasses = match ($size) {
        'small' => 'h-8 w-8 border-4 border-white ring-1 ring-slate-300',
        'medium' => 'h-12 w-12 border-4 border-white ring-1 ring-slate-300',
        'large' => 'h-16 w-16 border-4 border-white ring-1 ring-slate-300',
        default => 'h-8 w-8'
    };

    $nameClasses = match ($size) {
        'small' => 'text-xs font-medium',
        'medium' => 'text-sm font-semibold',
        'large' => 'text-base font-semibold',
        default => 'text-xs font-medium'
    };

    $dateClasses = match ($size) {
        'small' => 'text-xs text-zinc-600',
        'medium' => 'text-sm text-zinc-600',
        'large' => 'text-sm text-zinc-600',
        default => 'text-xs text-zinc-600'
    };
@endphp

<div class="flex items-center {{ $containerClasses }}" {{ $attributes }}>
    <a href="{{ route('sabhero-articles.author.show', $user->slug) }}" title="{{ $user->name() }}"
        class="hover:opacity-65">
        <img class="{{ $avatarClasses }} overflow-hidden rounded-full object-cover text-[0]"
            srcset="{{ $user->getAuthorMediaSrcSet() }}" src="{{ $user->getAuthorAvatarUrl() }}"
            alt="{{ $user->name() }}">
    </a>
    <div class="min-w-0">
        <a href="{{ route('sabhero-articles.author.show', $user->slug) }}" title="{{ $user->name() }}"
            class="block overflow-hidden text-ellipsis whitespace-nowrap hover:text-prime {{ $nameClasses }}">
            {{ $user->name() }}
        </a>
        @if($showDate && $post)
            <span class="block whitespace-nowrap font-medium {{ $dateClasses }}">
                {{ $post->formattedPublishedDate() }}
            </span>
        @endif
    </div>
</div>