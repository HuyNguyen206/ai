<x-layouts::app :title="__('AI Knowledge Search')">
    <section class="space-y-8">
        <div class="space-y-2">
            <flux:heading size="xl">{{ __('AI Knowledge Search') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Search internal docs and find similar tickets using embeddings.') }}
            </flux:text>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="sm" class="mb-3">{{ __('Help Center Search') }}</flux:heading>
            <form method="GET" action="{{ route('ai.knowledge-search') }}" class="space-y-3">
                <input type="hidden" name="ticket_id" value="{{ $ticketId ?? '' }}">
                <input
                    type="text"
                    name="q"
                    value="{{ $query ?? '' }}"
                    placeholder="{{ __('Search help center articles...') }}"
                    class="w-full rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-900 shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                />
                <flux:button variant="primary" type="submit">
                    {{ __('Search') }}
                </flux:button>
            </form>

            <div class="mt-4 space-y-3">
                @forelse ($documentResults as $result)
                    @php($doc = $result['document'])
                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:heading size="sm">{{ $doc->title }}</flux:heading>
                        <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Similarity: :score (min :min)', ['score' => number_format($result['score'], 3), 'min' => number_format($minSimilarity, 2)]) }}
                        </flux:text>
                        <flux:text class="mt-2 text-sm text-zinc-700 dark:text-zinc-200">
                            {{ \Illuminate\Support\Str::limit($doc->body, 160) }}
                        </flux:text>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-300 bg-zinc-50 p-6 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                        {{ __('No relevant documents found yet.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts::app>
