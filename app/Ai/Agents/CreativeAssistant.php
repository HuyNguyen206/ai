<?php

namespace App\Ai\Agents;

use App\Ai\Middleware\InputSafetyMiddleware;
use App\Ai\Middleware\OutputSafetyMiddleware;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;
use Stringable;

#[Provider(Lab::Gemini)]
#[UseCheapestModel]
#[MaxTokens(1200)]
class CreativeAssistant implements Agent, HasMiddleware, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<PROMPT
You are a helpful creative writing assistant.
Keep content safe, professional, and non-sensative.
PROMPT;    }


    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            //enable this one might caught rate limit error in gemeni
//            (new WebSearch)->allow([
//                'laracasts.com',
//                'laravel.com',
//                'php.net'
//            ])
        ];
    }

    public function middleware(): array
    {
        return [
            InputSafetyMiddleware::class,
            OutputSafetyMiddleware::class,
        ];
    }
}
