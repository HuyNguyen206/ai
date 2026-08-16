<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

<script>
    document.addEventListener('alpine:init', () => {
        // Alpine.js initialization code
        Alpine.data('ticketChatDemo', (ticketId, initialResponse = '') => ({
            ticketId: ticketId,
            prompt: '',
            response: initialResponse,
            async send() {
                const message = this.prompt.trim();

                if (message.length < 3) {
                    return
                }

                this.prompt = ''

                try {
                    const response = await fetch(`/tickets/${this.ticketId}/ai/chat`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ message })
                    });

                    const data = await response.json()

                    if(!response.ok) {
                        throw new Error(data.message)
                    }

                    this.response = data.data ?? ''
                } catch (error) {
                    console.error('Error sending message:', error);
                }

            }
        }))
    });
</script>
