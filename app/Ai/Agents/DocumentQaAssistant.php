<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\UseSmartestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\FileSearch;
use Laravel\Ai\Providers\Tools\FileSearchQuery;
use Stringable;

#[UseSmartestModel]
#[MaxTokens(1600)]
#[MaxSteps(3)]
class DocumentQaAssistant implements Agent, HasTools
{
    use Promptable;

    public function __construct(
        public readonly int     $teamId,
        public readonly int     $userId,
        public readonly string  $storeId,
        public readonly ?string $providerFileId = null,
    )
    {

    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<PROMPT
You are a support documentation assistant.
Only answer using the uploaded document available through the file_search tool.
If the answer is not in the documents, say you do not know.
After the answer, include a short "sources" list with file names.
PROMPT;
    }


    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new FileSearch([$this->storeId], function (FileSearchQuery $query) {
                $query->where('team_id', $this->teamId)
                    ->where('user_id', $this->userId);

                if ($this->providerFileId) {
                    $query->where('provider_file_id', $this->providerFileId);
                }
            })
        ];
    }
}
