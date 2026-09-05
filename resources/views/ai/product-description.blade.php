<x-layouts::app :title="__('Product Description Generator')">
    <section class="space-y-8">
        <div class="space-y-2">
            <flux:heading size="xl">{{ __('Product Description Generator') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Graceful AI generation with failover, timeouts, and a queue fallback.') }}
            </flux:text>
        </div>

        @if (!empty($status))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200">
                {{ $status }}
            </div>
        @endif

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="sm" class="mb-3">{{ __('Generate Description') }}</flux:heading>
            <form method="POST" action="{{ route('ai.product-description.store') }}" class="space-y-3">
                @csrf

                <input
                    type="text"
                    name="product_name"
                    placeholder="{{ __('Product name') }}"
                    class="w-full rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-900 shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                />

                <input
                    type="text"
                    name="audience"
                    placeholder="{{ __('Audience (optional)') }}"
                    class="w-full rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-900 shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                />

                <input
                    type="text"
                    name="tone"
                    placeholder="{{ __('Tone (optional)') }}"
                    class="w-full rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-900 shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                />

                <textarea
                    name="features"
                    rows="5"
                    placeholder="{{ __('Key features (one per line)') }}"
                    class="w-full rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-900 shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                ></textarea>

                <div class="flex flex-wrap gap-3">
                    <flux:button variant="primary" type="submit" name="mode" value="sync">
                        {{ __('Generate Now') }}
                    </flux:button>
                    <flux:button variant="ghost" type="submit" name="mode" value="async">
                        {{ __('Generate In Background') }}
                    </flux:button>
                </div>
            </form>
        </div>

        @if ($run)
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="sm" class="mb-3">{{ __('Latest Run') }}</flux:heading>

                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Status: :status', ['status' => $run->status]) }}
                </flux:text>

                @if ($run->error_message)
                    <div class="mt-2 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-950/40 dark:text-rose-200">
                        {{ $run->error_message }}
                    </div>
                @endif

                @if ($run->output_text)
                    <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-4 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                        {{ $run->output_text }}
                    </div>
                @else
                    <div class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('No output yet. Refresh this page in a few seconds.') }}
                    </div>
                @endif
            </div>
        @endif
    </section>
</x-layouts::app>
