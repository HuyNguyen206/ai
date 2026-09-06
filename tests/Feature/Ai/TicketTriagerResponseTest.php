<?php

use App\Ai\Agents\TicketTriager;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\StructuredAgentResponse;

it('triager returns correct response', function () {
     TicketTriager::fake([
         new StructuredAgentResponse(
             invocationId: 'invocation_test',
             structured: [
                 'priority' => 3,
                 'department' => 'Refund',
                 'sentiment' => 'negative',
                 'tags' => ['refund', 'billing'],
                 'summary' => 'The customer is requesting a refund for a billing issue.'
             ],
             text: 'Structure output',
             usage: new Usage(10, 20, 30, 0),
             meta: new \Laravel\Ai\Responses\Data\Meta('gemini', 'gemini-1')
         )
     ])->preventStrayPrompts();

     $ticket = \App\Models\Ticket::factory()->create([
         'subject' => 'Refund Request',
     ]);

     $response = TicketTriager::make()->prompt("Subject: {$ticket->subject}");

     expect($response['priority'])->toBe(3);
     expect($response['department'])->toBe('Refund');
     expect($response['sentiment'])->toBe('negative');
     expect($response['tags'])->toBe(['refund', 'billing']);
});
