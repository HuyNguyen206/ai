<?php

namespace App\Providers;

use App\Listeners\RecordAiUsage;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\Events\AgentPrompted;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AgentPrompted::class => [
            RecordAiUsage::class,
        ]
    ];
}
