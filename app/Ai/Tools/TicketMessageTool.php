<?php

namespace App\Ai\Tools;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class TicketMessageTool implements Tool
{
    public function __construct(public int $ticketId, public ?User $user = null) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Return the most recent tickets';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $user = $this->user ?? auth()->user();

        if (! $user) {
            return 'No user is authenticated.';
        }

        $ticket = Ticket::find($this->ticketId);

        if (empty($ticket)) {
            return 'Ticket not found.';
        }

        $count = $request->integer('count', 3);

        $messages = $ticket->messages()->latest()->limit($count)->get()->reverse()
            ->map(fn (TicketMessage $message) =>
            [
                "role" => $message->role,
                "body" => $message->body
            ])
        ->values();

        return $messages->toPrettyJson();
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'count' => $schema->integer()
                ->min(1)
                ->max(5)
                ->description('The number of messages to retrieve.'),
        ];
    }
}
