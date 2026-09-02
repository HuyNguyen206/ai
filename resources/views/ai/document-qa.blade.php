<x-layouts::app :title="__('Ask a Document')">
    <section class="space-y-8">
        <div class="space-y-2">
            <flux:heading size="xl">{{ __('Ask a Document') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Upload manuals or PDFs, then ask questions grounded in those files.') }}
            </flux:text>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        @if (!empty($error))
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-950/40 dark:text-rose-200">
                {{ $error }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="sm" class="mb-3">{{ __('Upload a Document') }}</flux:heading>
                <form method="POST" action="{{ route('ai.document-qa.upload') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input
                        type="file"
                        name="document"
                        accept=".pdf,.txt,.md"
                        class="w-full rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-900 shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    />
                    <flux:button variant="primary" type="submit">
                        {{ __('Upload & Index') }}
                    </flux:button>
                </form>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="sm" class="mb-3">{{ __('Ask a Question') }}</flux:heading>
                <form method="POST" action="{{ route('ai.document-qa.ask') }}" class="space-y-3">
                    @csrf
                    <select
                        name="document_id"
                        class="w-full rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-900 shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    >
                        <option value="">{{ __('All uploaded documents') }}</option>
                        @foreach ($documents as $document)
                            <option value="{{ $document->id }}" @selected(optional($selectedDocument)->id === $document->id)>
                                {{ $document->filename }}
                            </option>
                        @endforeach
                    </select>

                    <textarea
                        name="question"
                        rows="4"
                        placeholder="{{ __('Ask a question about the document...') }}"
                        class="w-full rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-900 shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    >{{ $question ?? '' }}</textarea>

                    <flux:button variant="primary" type="submit">
                        {{ __('Ask Document') }}
                    </flux:button>
                </form>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="sm" class="mb-3">{{ __('Your Uploaded Documents') }}</flux:heading>

            <div class="space-y-3">
                @forelse ($documents as $document)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div>
                            <flux:heading size="sm">{{ $document->filename }}</flux:heading>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Stored in vector store: :id', ['id' => $document->provider_store_id]) }}
                            </flux:text>
                        </div>
                        <form method="POST" action="{{ route('ai.document-qa.delete', $document) }}">
                            @csrf
                            @method('DELETE')
                            <flux:button variant="danger" type="submit">
                                {{ __('Delete') }}
                            </flux:button>
                        </form>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-300 bg-zinc-50 p-6 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                        {{ __('No documents uploaded yet.') }}
                    </div>
                @endforelse
            </div>
        </div>

        @if (!empty($answer))
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="sm" class="mb-3">{{ __('Answer') }}</flux:heading>
                <flux:text class="text-sm text-zinc-700 dark:text-zinc-200">
                    {{ $answer }}
                </flux:text>

                <div class="mt-4 space-y-2">
                    <flux:heading size="xs">{{ __('Sources') }}</flux:heading>
                    @forelse ($sources as $source)
                        <div class="rounded-lg border border-zinc-200 bg-white p-3 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                            <div class="font-medium text-zinc-800 dark:text-zinc-100">
                                {{ $source['filename'] ?? __('Unknown') }}
                            </div>
                            @if (!empty($source['score']))
                                <div>{{ __('Score: :score', ['score' => number_format($source['score'], 3)]) }}</div>
                            @endif
                            @if (!empty($source['snippet']))
                                <div class="mt-1">
                                    {{ \Illuminate\Support\Str::limit($source['snippet'], 160) }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('No file search sources were returned for this answer.') }}
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </section>
</x-layouts::app>
