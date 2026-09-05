<?php

namespace App\Http\Controllers;

use App\Ai\Agents\CreativeAssistant;
use App\Models\AiRun;
use Illuminate\Http\Request;

class CreativeAssistantController extends Controller
{
    public function index()
    {
        return view('ai.creative-assistant', [
            'answer' => null,
            'prompt' => null,
            'error' => null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'prompt' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $run = AiRun::create([
            'team_id' => $request->user()->current_team_id,
            'user_id' => $request->user()->id,
            'ticket_id' => null,
            'feature_key' => 'creative_assistant',
            'status' => 'running',
            'provider' => 'gemini',
            'model' => null,
            'input_hash' => sha1($request->string('prompt')),
            'started_at' => now(),
        ]);

        $agent = new CreativeAssistant;

        try {
            $response = $agent->prompt($request->string('prompt')->toString());
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            return view('ai.creative-assistant', [
                'answer' => null,
                'prompt' => $request->string('prompt')->toString(),
                'error' => $e->getMessage(),
            ]);
        }

        $run->update([
            'status' => 'succeeded',
            'finished_at' => now(),
            'output_text' => (string) $response,
            'provider' => $response->meta->provider ?? $run->provider,
            'model' => $response->meta->model ?? $run->model,
            'invocation_id' => $response->invocationId ?? null,
        ]);

        return view('ai.creative-assistant', [
            'answer' => (string) $response,
            'prompt' => $request->string('prompt')->toString(),
            'error' => null,
        ]);
    }
}
