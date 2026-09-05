<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ProductDescriptionAgent;
use App\Models\AiRun;
use App\Models\AiUsage;
use Illuminate\Http\Request;

class AiProductDescriptionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $teamId = $this->ensureTeamAccess($user); // tenant guard

        $runId = $request->integer('run');
        $run = null;

        if ($runId) {
            $run = AiRun::whereKey($runId)
                ->where('team_id', $teamId)
                ->where('user_id', $user->id)
                ->first();
        }

        return view('ai.product-description', [
            'run' => $run,
            'status' => session('status'),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $teamId = $this->ensureTeamAccess($user); // tenant guard

        $request->validate([
            'product_name' => ['required', 'string', 'min:2', 'max:120'],
            'audience' => ['nullable', 'string', 'max:120'],
            'tone' => ['nullable', 'string', 'max:60'],
            'features' => ['required', 'string', 'min:10', 'max:2000'],
            'mode' => ['required', 'in:sync,async'],
        ]);

        $prompt = $this->buildPrompt(
            name: $request->string('product_name')->toString(),
            audience: $request->string('audience')->toString(),
            tone: $request->string('tone')->toString(),
            features: $request->string('features')->toString(),
        );

        $run = AiRun::create([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'ticket_id' => null, // not tied to a ticket
            'feature_key' => 'product_description',
            'status' => 'running',
            'provider' => 'openai',
            'model' => null,
            'input_hash' => sha1($teamId.'|'.$request->string('product_name').'|'.$request->string('features')),
            'started_at' => now(),
        ]);

        $agent = new ProductDescriptionAgent();

        if ($request->string('mode')->toString() === 'async') {
            $this->queueGeneration($run, $agent, $prompt); // explicit async path

            return redirect()
                ->route('ai.product-description', ['run' => $run->id])
                ->with('status', 'Queued: we will finish this in the background.');
        }

        try {
            $response = $agent->prompt($prompt); // sync path
        } catch (\Throwable $e) {
            // Graceful degradation: fall back to the queue.
            $run->update([
                'status' => 'queued',
                'error_message' => $e->getMessage(),
            ]);

            $this->queueGeneration($run, $agent, $prompt);

            return redirect()
                ->route('ai.product-description', ['run' => $run->id])
                ->with('status', 'Provider slow or unavailable — queued for background processing.');
        }

        $run->update([
            'status' => 'succeeded',
            'finished_at' => now(),
            'output_text' => (string) $response,
            'error_message' => null,
        ]);

        if (isset($response->usage)) {
            $usage = $response->usage;

            AiUsage::create([
                'ai_run_id' => $run->id,
                'prompt_tokens' => $usage->promptTokens ?? 0,
                'completion_tokens' => $usage->completionTokens ?? 0,
                'total_tokens' => $usage->totalTokens ?? 0,
                'cost_usd' => $usage->costUsd ?? null,
            ]);
        }

        return redirect()
            ->route('ai.product-description', ['run' => $run->id]);
    }

    protected function queueGeneration(AiRun $run, ProductDescriptionAgent $agent, string $prompt): void
    {
        $run->update([
            'status' => 'queued',
        ]);

        $agent->queue($prompt)
            ->then(function ($response) use ($run) {
                $freshRun = AiRun::find($run->id);

                if (! $freshRun) {
                    return;
                }

                $freshRun->update([
                    'status' => 'succeeded',
                    'finished_at' => now(),
                    'output_text' => (string) $response,
                    'error_message' => null,
                ]);

                if (isset($response->usage)) {
                    $usage = $response->usage;

                    AiUsage::create([
                        'ai_run_id' => $freshRun->id,
                        'prompt_tokens' => $usage->promptTokens ?? 0,
                        'completion_tokens' => $usage->completionTokens ?? 0,
                        'total_tokens' => $usage->totalTokens ?? 0,
                        'cost_usd' => $usage->costUsd ?? null,
                    ]);
                }
            })
            ->catch(function (\Throwable $e) use ($run) {
                $freshRun = AiRun::find($run->id);

                if (! $freshRun) {
                    return;
                }

                $freshRun->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);
            });
    }

    protected function buildPrompt(string $name, string $audience, string $tone, string $features): string
    {
        $audienceLine = $audience !== '' ? "Audience: {$audience}" : 'Audience: general customers';
        $toneLine = $tone !== '' ? "Tone: {$tone}" : 'Tone: friendly and clear';

        return <<<PROMPT
Product: {$name}
{$audienceLine}
{$toneLine}

Key Features:
{$features}

Write a product description that highlights benefits, includes 3-5 bullet points, and ends with a short call-to-action.
PROMPT;
    }

    protected function ensureTeamAccess($user): int
    {
        $teamId = $user->current_team_id;

        if (! $user->teams()->whereKey($teamId)->exists()) {
            abort(403); // stale team id
        }

        return $teamId;
    }
}
