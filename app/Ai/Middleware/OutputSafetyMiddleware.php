<?php

namespace App\Ai\Middleware;

use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

class OutputSafetyMiddleware
{
    public function handle(AgentPrompt $prompt, \Closure $next)
    {
        $response = $next($prompt);

        $text = (string) $response;

        $blocked = [
            'ssn',
            'phish',
        ];

        foreach ($blocked as $word) {
            if (str_contains($text, $word)) {
                return new AgentResponse(
                    $response->invocationId,
                    'Output blocked due to safety concerns.',
                    $response->usage,
                    $response->meta
                );
            }
        }

        return $response;
    }
}
