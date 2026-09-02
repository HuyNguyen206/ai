<?php

namespace App\Http\Controllers;

use App\Ai\Agents\DocumentQaAssistant;
use App\Models\AiRun;
use App\Models\AiUsage;
use App\Models\UploadedDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Ai\Files;
use Laravel\Ai\Files\Document as AiDocument;
use Laravel\Ai\Stores;

class AiDocumentQAController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $teamId = $this->ensureTeamAccess($user); // guard against stale team IDs

        $documents = UploadedDocument::where('team_id', $teamId)
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return view('ai.document-qa', [
            'documents' => $documents,
            'answer' => null,
            'question' => null,
            'sources' => [],
            'selectedDocument' => null,
            'error' => null,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $teamId = $this->ensureTeamAccess($user); // tenant scoping

        $request->validate([
            'document' => ['required', 'file', 'max:10240', 'mimes:pdf,txt,md'],
        ]);

        $upload = $request->file('document');
        $store = $this->resolveTeamStore($teamId); // reuse a single store per team

        // Prepare the document for provider upload.
        $document = AiDocument::fromUpload($upload);

        // Store the file with the provider to get a stable file ID.
        $storedFile = Files::put($document);

        $metadata = [
            'team_id' => $teamId,
            'user_id' => $user->id,
            'provider_file_id' => $storedFile->id,
            'filename' => $upload->getClientOriginalName(),
        ];

        // Add the file to the vector store with metadata.
        $added = $store->add($storedFile, $metadata);

        $metadata['provider_document_id'] = $added->id();

        UploadedDocument::create([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'filename' => $upload->getClientOriginalName(),
            'provider_file_id' => $storedFile->id,
            'provider_store_id' => $store->id,
            'metadata' => $metadata,
        ]);

        return redirect()
            ->route('ai.document-qa')
            ->with('status', 'Document uploaded and indexed.');
    }

    public function ask(Request $request)
    {
        $user = $request->user();
        $teamId = $this->ensureTeamAccess($user); // tenant scoping

        $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:2000'],
            'document_id' => ['nullable', 'integer'],
        ]);

        $documents = UploadedDocument::where('team_id', $teamId)
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $selectedDocument = null;
        $storeId = UploadedDocument::where('team_id', $teamId)
            ->where('user_id', $user->id)
            ->value('provider_store_id');

        if ($request->filled('document_id')) {
            $selectedDocument = UploadedDocument::where('team_id', $teamId)
                ->where('user_id', $user->id)
                ->findOrFail($request->integer('document_id'));

            $storeId = $selectedDocument->provider_store_id;
        }

        if (! $storeId) {
            return view('ai.document-qa', [
                'documents' => $documents,
                'answer' => null,
                'question' => $request->string('question')->toString(),
                'sources' => [],
                'selectedDocument' => $selectedDocument,
                'error' => 'Upload a document before asking a question.',
            ]);
        }

        $run = AiRun::create([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'ticket_id' => null, // this feature is not tied to a ticket
            'feature_key' => 'document_qa',
            'status' => 'running',
            'provider' => 'openai',
            'model' => null,
            'input_hash' => sha1($teamId.'|'.$request->string('question').'|'.$request->integer('document_id')),
            'started_at' => now(),
        ]);

        try {
            $agent = new DocumentQaAssistant(
                teamId: $teamId,
                userId: $user->id,
                storeId: $storeId,
                providerFileId: $selectedDocument?->provider_file_id,
            );

            // Ask the question; the tool will retrieve relevant chunks.
            $response = $agent->prompt("Question:\n".$request->string('question'));
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $run->update([
            'status' => 'succeeded',
            'finished_at' => now(),
            'output_text' => (string) $response,
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

        $sources = $this->normalizeSources($response->toolResults->all());

        return view('ai.document-qa', [
            'documents' => $documents,
            'answer' => (string) $response,
            'question' => $request->string('question')->toString(),
            'sources' => $sources,
            'selectedDocument' => $selectedDocument,
            'error' => null,
        ]);
    }

    public function destroy(Request $request, UploadedDocument $uploadedDocument)
    {
        $user = $request->user();
        $teamId = $this->ensureTeamAccess($user); // tenant scoping

        if ($uploadedDocument->team_id !== $teamId || $uploadedDocument->user_id !== $user->id) {
            abort(404); // don’t leak existence across teams/users
        }

        $store = Stores::get($uploadedDocument->provider_store_id);
        $documentId = data_get($uploadedDocument->metadata, 'provider_document_id', $uploadedDocument->provider_file_id);

        // Remove from store first, then delete the provider file.
        $store->remove($documentId, deleteFile: false);
        Files::delete($uploadedDocument->provider_file_id);

        $uploadedDocument->delete();

        return redirect()
            ->route('ai.document-qa')
            ->with('status', 'Document removed.');
    }

    protected function ensureTeamAccess($user): int
    {
        $teamId = $user->current_team_id;

        if (! $user->teams()->whereKey($teamId)->exists()) {
            abort(403); // stale team id
        }

        return $teamId;
    }

    protected function resolveTeamStore(int $teamId)
    {
        $existingStoreId = UploadedDocument::where('team_id', $teamId)
            ->value('provider_store_id');

        if ($existingStoreId) {
            return Stores::get($existingStoreId); // reuse existing store
        }

        return Stores::create(
            name: "supportdesk-team-{$teamId}",
            description: "SupportDesk uploads for team {$teamId}",
        );
    }

    protected function normalizeSources(array $toolResults): array
    {
        $sources = [];

        foreach ($toolResults as $toolResult) {
            if (! in_array($toolResult->name, ['file_search', 'fileSearch'], true)) {
                continue; // ignore other tool results
            }

            $raw = $toolResult->result;

            if (! is_array($raw)) {
                continue; // provider returned non‑array data
            }

            $items = $raw['data'] ?? $raw['results'] ?? $raw['files'] ?? null;

            if (! is_array($items)) {
                $sources[] = [
                    'filename' => null,
                    'score' => null,
                    'snippet' => Str::limit(json_encode($raw), 200),
                ];
                continue;
            }

            foreach ($items as $item) {
                $snippet = $item['text'] ?? null;

                if (! $snippet && isset($item['content'])) {
                    $snippet = is_array($item['content'])
                        ? ($item['content'][0]['text'] ?? null)
                        : $item['content'];
                }

                $sources[] = [
                    'filename' => $item['filename'] ?? $item['file_name'] ?? $item['file'] ?? $item['name'] ?? 'Unknown',
                    'score' => $item['score'] ?? $item['similarity'] ?? null,
                    'snippet' => $snippet,
                ];
            }
        }

        return $sources;
    }
}
