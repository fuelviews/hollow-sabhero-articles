<x-sabhero-articles-layout>
    <section class="py-8">
        <div class="container mx-auto">
            <h1 class="text-3xl font-semibold mb-8">Our Authors</h1>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($authors as $user)
                    <div class="bg-white p-6 rounded-lg shadow">
                        <a href="{{ route('sabhero-articles.author.show', $user->slug) }}">

                            <img class="h-48 w-48 rounded-full mx-auto hover:opacity-65"
                                srcset="{{ $user->getAuthorMediaSrcSet() }}" src="{{ $user->getAuthorAvatarUrl() }}"
                                alt="{{ $user->name }}">
                            <h2 class="mt-4 text-xl font-semibold text-center hover:text-prime">{{ $user->name }}</h2>
                        </a>
                        <p class="text-center mt-2 text-gray-600">{{ $user->posts_count }}
                            {{ Str::plural('post', $user->posts_count) }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-8">
                {{ $authors->links() }}
            </div>
            @if ($authors->isEmpty())
                <div class="container mx-auto">
                    <div class="flex justify-center">
                        <p class="text-2xl font-semibold text-gray-300">No authors found</p>
                    </div>
                </div>
            @endif
        </div>
    </section>
</x-sabhero-articles-layout>