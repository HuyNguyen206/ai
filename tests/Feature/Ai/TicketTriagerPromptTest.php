<?php

use App\Ai\Agents\TicketTriager;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\StructuredAgentResponse;

it('triager prompt correctly', function () {
    TicketTriager::fake()->preventStrayPrompts();

    $ticket = \App\Models\Ticket::factory()->create([
        'subject' => $prompt = 'Refund Request',
    ]);
    \Pest\Laravel\actingAs(\App\Models\User::factory()->create());

    \Pest\Laravel\postJson(route('tickets.ai.triager', $ticket), [
        'message' => 'I would like a refund for my recent purchase.',
    ]);

    TicketTriager::assertPrompted(fn(\Laravel\Ai\Prompts\AgentPrompt $prompt)
    => $prompt->contains("Subject: {$ticket->subject}"));
});
