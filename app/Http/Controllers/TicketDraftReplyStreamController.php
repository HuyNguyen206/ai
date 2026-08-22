<?php

namespace App\Http\Controllers;

use App\Ai\Agents\TicketAssistant;
use App\Models\AiRun;
use App\Models\AiUsage;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Laravel\Ai\Responses\StreamedAgentResponse;

class TicketDraftReplyStreamController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Ticket $ticket)
    {
        $agent = new TicketAssistant($ticket->id);
        $prompt = 'Draft a concise, friendly reply to the most recent user message';

        $run = AiRun::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'team_id' => $ticket->team_id,
            'started_at' => now(),
            'feature_key' => 'draft_reply',
            'status' => 'running',
            'provider' => config('ai.default'),
            'model' => null,
            'input_hash' => sha1($ticket->id . '|' . $request->string('message')),
        ]);

        $stream = $agent->stream($prompt);

        $stream->then(function (StreamedAgentResponse $response) use ($run) {
            $run->update([
                'status' => 'success',
                'finished_at' => now(),
                'output_text' => $response->text,
                'provider' => $response->meta->provider,
                'model' => $response->meta->model ,
            ]);

            if (isset($response->usage)) {
                AiUsage::create([
                    'ai_run_id' => $run->id,
                    'prompt_tokens' => $response->usage->promptTokens ?? 0,
                    'completion_tokens' => $response->usage->completionTokens ?? 0,
                    'total_tokens' => $response->usage->totalTokens ?? 0,
                    'cost_usd' => $response->usage->costUsd ?? null,
                ]);
            }
        });

        return $stream;
    }
}
