<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Support\Vector;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laravel\Ai\Embeddings;

class AiKnowledgeSearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $team = $user->current_team_id;

        $documentResults = collect();
        $query = $request->string('q')->trim();
        $minSimilarity = 0.50;

        if ($query->isNotEmpty()) {
            $queryEmbedding = Embeddings::for([$query->toString()])->generate()->first();

            $documentResults = Document::where('team_id', $team)
//                ->whereNull('embedding')
                ->get()
                ->map(function (Document $document) use ($queryEmbedding) {

                    if ($embedding = $document->embedding) {
                        $embedding = !is_array($embedding) ? [$embedding] : $embedding;

                        return [
                            'document' => $document,
                            'score' => Vector::cosine($queryEmbedding, $embedding),
                        ];
                    }

                    $embeddingCalculated = Embeddings::for(
                        ["{$document->title}\n\n{$document->body}"]
                    )->generate()->first();

                    $document->update([
                        'embedding' => $embeddingCalculated,
                    ]);

                    return [
                        'document' => $document,
                        'score' => Vector::cosine($queryEmbedding, $embeddingCalculated),
                    ];
                })
                ->filter(fn ($documentResult) => $documentResult['score'] >= $minSimilarity)
                ->sortByDesc('score')
                ->take(5)
                ->values();
        }

        return view('ai.knowledge-search', [
            'query' => $query->toString(),
            'documentResults' => $documentResults,
            'minSimilarity' => $minSimilarity,
        ]);
    }
}
