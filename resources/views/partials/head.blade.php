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

        Alpine.data('ticketDraftDemo', (ticketId, initialDraft = '') => ({
            ticketId: ticketId,
            draft: initialDraft,
            controller: null,
            streaming: false,

            // Characters received from the server but not yet painted.
            pending: '',
            typer: null,

            // Typing pace. ~2 chars every 16ms frame = ~125 chars/sec.
            minCharsPerFrame: 2,
            frameMs: 16,

            enqueue(text) {
                this.pending += text;
                this.startTyping();
            },

            startTyping() {
                if (this.typer) {
                    return
                }

                this.typer = setInterval(() => {
                    if (this.pending.length === 0) {
                        // Nothing buffered and the network is done: stop the timer.
                        if (!this.streaming) {
                            this.stopTyping();
                        }

                        return
                    }

                    // Drain faster when we have fallen behind, so a burst of
                    // tokens never leaves the text trailing seconds behind.
                    const size = Math.max(this.minCharsPerFrame, Math.ceil(this.pending.length / 20));

                    this.draft += this.pending.slice(0, size);
                    this.pending = this.pending.slice(size);
                }, this.frameMs);
            },

            stopTyping() {
                clearInterval(this.typer);
                this.typer = null;
            },

            cancelStream() {
                this.controller?.abort();
                this.streaming = false;
                this.pending = '';
                this.stopTyping();
            },
            insertIntoReply() {
                const replyBox = document.querySelector('[data-ticket-reply]');

                replyBox.value = this.draft ;
            },
            async streamDraft() {
                if (this.streaming) {
                    return
                }

                this.draft = '';
                this.pending = '';
                this.streaming = true;
                this.controller = new AbortController();

                try {
                    const response = await fetch(`/tickets/${this.ticketId}/ai/draft-reply/stream`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'text/event-stream',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        signal: this.controller.signal
                    });

                    if (!response.ok) {
                        throw new Error('Failed to stream draft reply');
                    }

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';

                    while (true) {
                        const { value, done } = await reader.read();
                        if (done) break;

                        buffer += decoder.decode(value, { stream: true });

                        const parts = buffer.split('\n\n');

                        buffer = parts.pop() ?? '';

                        parts.forEach(part => {
                            if (!part.startsWith('data: ')) {
                                return
                            }

                            const payload = part.replace('data: ', '').trim();
                            if (payload === '[DONE]') {
                                return
                            }

                            try {
                                const event = JSON.parse(payload);
                                if (event.type === 'text_delta') {
                                    this.enqueue(event.delta);
                                }
                            } catch (error) {
                                console.error('Error parsing SSE event:', error);
                            }
                        })
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Error streaming draft:', error);
                    }
                } finally {
                    // Let the typer drain whatever is still buffered, then stop.
                    this.streaming = false;
                }
            }
        }))
    });
</script>
