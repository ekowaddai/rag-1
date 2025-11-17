<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class ChatController extends Controller
{
    /**
     * @param Request $request
     * @return string
     */
    public function __invoke(Request $request): string
    {
        $prompt = $request->post('content');

        // 1. Generate embedding of user's query
        $embeddingResponse = Http::post('http://localhost:11434/api/embeddings', [
            'model' => 'llama3.2:1b',
            'prompt' => $prompt
        ]);

        if ($embeddingResponse->failed()) {
            return response()->json(['error' => 'Failed to generate embedding'], 500);
        }

        $queryEmbedding = $embeddingResponse->json()['embedding']; // array of floats

        // 2. Build similarity score SQL (dot product)
        $placeholders = implode(', ', array_fill(0, count($queryEmbedding), '?'));

        $scoreSelect = [];
        foreach ($queryEmbedding as $index => $value) {
            $scoreSelect[] = "embedding->'\$[{$index}]' * ?";
        }
        $scoreSql = implode(' + ', $scoreSelect);

        // 3. Retrieve top 3 most similar documents
        $params = $queryEmbedding;

        $documents = DB::table('documents')
            ->select('title', 'content')
            ->selectRaw("($scoreSql) AS score", $params)
            ->orderByDesc('score')
            ->limit(3)
            ->get();

        // 4. Build context string
        $context = "";
        foreach ($documents as $doc) {
            $context .= "Title: {$doc->title}\nContent: {$doc->content}\n\n";
        }

        // 5. Final prompt to Ollama
        $finalPrompt = "Use ONLY the context below to answer the question.\n\n".
                    "CONTEXT:\n{$context}\n\n".
                    "QUESTION: {$prompt}\n".
                    "ANSWER:";

        // 6. Ask Ollama with context
        $response = Http::post('http://localhost:11434/api/generate', [
            'model' => 'llama3.2:1b',
            'prompt' => $finalPrompt,
            'stream' => false
        ]);

        if ($response->failed()) {
            return response()->json([
                'error' => $response->json(),
                'message' => 'Ollama request failed'
            ], 500);
        }

        return $response->json()['response'] ?? 'No response from Ollama';
    }
}
