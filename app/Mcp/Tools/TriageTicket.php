<?php

namespace App\Mcp\Tools;

use App\Ai\Agents\TicketTriager;
use App\Models\AiRun;
use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Allow to triage a support ticket using AI.')]
class TriageTicket extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'ticket_id' => 'required|integer|min:1',
        ]);

        $ticket = Ticket::findOrFail($data['ticket_id']);

        $body = $ticket->messages()->latest()->first()?->body;
        $prompt = "Subject: {$ticket->subject} \n\n {$body}";

        $aiRun = AiRun::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'team_id' => $ticket->team_id,
            'started_at' => now(),
            'feature_key' => 'ticket_triager_mcp_tool',
            'status' => 'running',
            'provider' => config('ai.default'),
            'model' => null,
            'input_hash' => sha1($ticket->subject . '|' . $body)
        ]);

        try {
            $response = app(TicketTriager::class)->prompt($prompt);
        } catch (\Throwable $exception) {
            $aiRun->update([
                'status' => 'failed',
                'finished_at' => now(),
            ]);

             throw $exception;
        }

        $aiRun->update([
            'status' => 'success',
            'finished_at' => now(),
            'invocation_id' => $response->invocationId
        ]);

        return Response::structured([
            'priority' => $response['priority'],
            'department' => $response['department'],
            'sentiment' => $response['sentiment'],
            'tags' => $response['tags'],
            'summary' => $response['summary'] ?? null,
        ])->responses()->first();
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->min(1)
        ];
    }
}
