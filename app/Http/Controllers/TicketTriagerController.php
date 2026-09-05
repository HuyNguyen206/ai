<?php

namespace App\Http\Controllers;

use App\Ai\Agents\TicketTriager;
use App\Models\AiRun;
use App\Models\AiUsage;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketTriagerController extends Controller
{
    public function __invoke(Request $request, Ticket  $ticket)
    {
        try {
            $body = $ticket->messages()->latest()->first()?->body;
            $prompt = "Subject: {$ticket->subject} \n\n {$body}";

            $aiRun = AiRun::create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'team_id' => $ticket->team_id,
                'started_at' => now(),
                'feature_key' => 'ticket_triager',
                'status' => 'running',
                'provider' => config('ai.default'),
                'model' => null,
                'input_hash' => sha1($ticket->subject . '|' . $body)
            ]);

            $response = app(TicketTriager::class)->prompt($prompt);

            $ticket->update([
                'priority' => $response['priority'],
                'department' => $response['department'],
                'sentiment' => $response['sentiment'],
                'ai_tags' => $response['tags'],
            ]);

            if (!empty($response['summary'])) {
                $ticket->messages()->create([
                    'user_id' => null,
                    'role' => 'System',
                    'body' => "AI Summary: {$response['summary']}",
                ]);
            }

            $aiRun->update([
                'status' => 'success',
                'finished_at' => now(),
                'invocation_id' => $response->invocationId
            ]);

            return back();
        }
         catch (\Throwable $exception) {
             report($exception);

             $aiRun->update([
                 'status' => 'failed',
                 'finished_at' => now(),
                 'error_message' => $exception->getMessage(),
             ]);

             throw $exception;
//             return response()->json([
//                 'message' => $exception->getMessage(),
//                 'data' => $response ?? null,
//                 'success' => false,
//             ], 500);
         }
    }
}
