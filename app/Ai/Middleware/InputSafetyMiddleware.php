<?php

namespace App\Ai\Middleware;

use Laravel\Ai\Prompts\AgentPrompt;

class InputSafetyMiddleware
{
    public function handle(AgentPrompt $prompt, \Closure $next)
    {
        $text = $prompt->prompt;

        $blocked = [
            'password',
            'credit card',
            'api key'
        ];

        foreach ($blocked as $word) {
            if (str_contains($text, $word)) {
                throw new \InvalidArgumentException("Input contains blocked content: {$word}");
            }
        }

        return $next($prompt);
    }
}
