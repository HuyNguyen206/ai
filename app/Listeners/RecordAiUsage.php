<?php

namespace App\Listeners;

use App\Models\AiUsage;
use Laravel\Ai\Events\AgentPrompted;

class RecordAiUsage
{
    public function handle(AgentPrompted $event)
    {
        $usage = $event->response?->usage;

        if (!$usage) {
            return;
        }

        if (AiUsage::where('invocation_id', $event->invocationId)->exists()) {
            return;
        }

        AiUsage::create([
            'invocation_id' => $event->invocationId,
            'prompt_tokens' => $promptTokens = $usage->promptTokens ?? 0,
            'completion_tokens' => $completionTokens = $usage->completionTokens ?? 0,
            'total_tokens' => $promptTokens + $completionTokens,
            'cost_usd' => $usage->costUsd ?? null,
        ]);
    }
}
