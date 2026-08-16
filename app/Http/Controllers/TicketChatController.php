<?php

namespace App\Http\Controllers;

use App\Ai\Agents\TicketAssistant;
use App\Models\AiRun;
use App\Models\AiUsage;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketChatController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Ticket $ticket)
    {
        $request->validate([
            'message' => 'required|string|max:2000'
        ]);

        $agent = new TicketAssistant($ticket->id);
        $prompt = "\n\nUser message:\n" . $request->string('message');

        $aiRun = AiRun::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'team_id' => $ticket->team_id,
            'started_at' => now(),
            'feature_key' => 'ticket_chat',
            'status' => 'running',
            'provider' => config('ai.default'),
            'model' => null,
            'input_hash' => sha1($ticket->id . '|' . $request->string('message')),
        ]);
        try {
            if ($ticket->ai_conversation_id) {
                $response = $agent->continue(conversationId: $ticket->ai_conversation_id, as: $request->user())
                    ->prompt($prompt);
            } else {
                $response = $agent->forUser($request->user())
                    ->prompt($prompt);

                $ticket->update([
                    'ai_conversation_id' => $response->conversationId()
                ]);
            }

        } catch (\Throwable $exception) {
            report($exception);
            $aiRun->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $aiRun->update([
            'status' => 'success',
            'finished_at' => now(),
        ]);

        if (isset($response->usage)) {
            AiUsage::create([
                'ai_run_id' => $aiRun->id,
                'prompt_tokens' => $response->usage->promptTokens ?? 0,
                'completion_tokens' => $response->usage->completionTokens ?? 0,
                'total_tokens' => $response->usage->totalTokens ?? 0,
                'cost_usd' => $response->usage->costUsd ?? null,
            ]);
        }

        $ticket->messages()->create([
            'user_id' => auth()->id(),
            'role' => 'user',
            'body' => $request->string('message'),
        ]);

        $ticket->messages()->create([
            'user_id' =>null,
            'role' => 'agent',
            'body' => (string) $response,
        ]);

        return redirect()->back();
    }
}
