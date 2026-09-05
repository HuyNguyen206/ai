<!-- resources/views/ai/creative-assistant.blade.php -->

<x-layouts::app :title="__('Creative Assistant')">
    <div class="flex flex-col gap-6">
        <flux:heading size="lg">Creative Assistant</flux:heading>

        <form method="POST" action="{{ route('ai.creative-assistant.store') }}" class="space-y-4">
            @csrf

            <flux:textarea name="prompt" rows="6" placeholder="Write a product tagline...">{{ old('prompt', $prompt) }}</flux:textarea>

            <flux:button type="submit">Generate</flux:button>
        </form>

        @if ($answer)
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="sm">Response</flux:heading>
                <flux:text class="mt-2">{{ $answer }}</flux:text>
            </div>
        @endif

        @if ($error)
            <flux:callout variant="danger">{{ $error }}</flux:callout>
        @endif
    </div>
</x-layouts::app>
