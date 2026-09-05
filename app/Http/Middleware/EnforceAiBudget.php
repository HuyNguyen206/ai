<?php

namespace App\Http\Middleware;

use App\Models\AiUsage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceAiBudget
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $teamId = $request->user()?->current_team_id;

        if (!$teamId) {
            return $next($request);
        }

        $tokensToday = AiUsage::where('team_id', $teamId)
            ->whereDate('created_at', now()->toDateString())
            ->sum('total_tokens');

        if ($tokensToday >= config('ai.daily_team_token_budget')) {
            abort(429, 'Daily AI token budget exceeded for your team.');
        }

        return $next($request);
    }
}
