<?php

namespace App\Ai\Agents;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
#[MaxTokens(1500)]
class TicketAssistant implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function __construct(public int $ticketId)
    {

    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $context = $this->ticketContext();

        return <<<PROMPT
        You are a support assistant. Stay strictly within the current ticket.
        If you are unsure, ask a clarifying question.

        $context
PROMPT;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return TicketMessage::where('ticket_id', $this->ticketId)
            ->get()
            ->map(function (TicketMessage $message) {
                $role = is_null($message->user_id)
                    ? MessageRole::Assistant
                    : MessageRole::User;

                return new Message($role, $message->body);
            })->toArray();    }

    public function ticketContext(): string
    {
        $ticket = Ticket::with([
            'messages' => fn($query) => $query->latest()->limit(5),
            'tags'
        ])->find($this->ticketId);

        if (!$ticket) {
            return 'Context not available.';
        }

        $tags = $ticket->tags->implode('name', ', ');
        $department = $ticket->department ?? 'n/a';
        $sentiment = $ticket->sentiment ?? 'n/a';
        $tagsText = $tags ?: 'none';
        $messages = $ticket->messages
            ->reverse()
            ->map(fn(TicketMessage $message) => "{$message->role}: {$message->body}")
            ->implode('\n');

        return  <<<CONTEXT
            Ticket Context:
                - Subject: {$ticket->subject}
                - Status: {$ticket->status}
                - Priority: {$ticket->priority}
                - Department: ($department}
                - Sentiment: {$sentiment}
                - Tags. ($tagsText)

            Recent Messages:
                $messages
CONTEXT;

    }

}
