<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RagService
{
    protected string $model = 'llama3.2:1b';

    /**
     * Generate embedding from Ollama
     */
    public function embed(string $text): array
    {
        $response = Http::post('http://localhost:11434/api/embeddings', [
            'model' => $this->model,
            'prompt' => $text
        ]);

        if ($response->failed()) {
            throw new \Exception("Failed to generate embedding");
        }

        return $response->json()['embedding'];
    }

    /**
     * Retrieve relevant documents based on vector similarity
     */
    public function retrieveRelevantDocuments(array $queryEmbedding, int $limit = 3)
    {
        // Build raw SQL for dot product
        $scoreParts = [];
        foreach ($queryEmbedding as $i => $val) {
            $scoreParts[] = "embedding->'\$[{$i}]' * ?";
        }
        $scoreSql = implode(' + ', $scoreParts);

        // Query DB
        return DB::table('documents')
            ->select('title', 'content')
            ->selectRaw("($scoreSql) AS score", $queryEmbedding)
            ->orderByDesc('score')
            ->limit($limit)
            ->get();
    }

    /**
     * Build context string
     */
    public function buildContext($documents): string
    {
        $context = "";
        foreach ($documents as $doc) {
            $context .= "Title: {$doc->title}\nContent: {$doc->content}\n\n";
        }
        return $context;
    }

    /**
     * Ask Ollama using the built prompt
     */
    public function askOllama(string $context, string $question): string
    {
        $finalPrompt = "Use ONLY the context below to answer the question.\n\n".
                       "CONTEXT:\n{$context}\n\n".
                       "QUESTION: {$question}\n".
                       "ANSWER:";

        $response = Http::post('http://localhost:11434/api/generate', [
            'model' => $this->model,
            'prompt' => $finalPrompt,
            'stream' => false
        ]);

        if ($response->failed()) {
            return "Error: Ollama request failed.";
        }

        return $response->json()['response'] ?? 'No response from Ollama';
    }

    /**
     * RAG Pipeline: embed → retrieve → generate answer
     */
    public function answerWithRag(string $query): string
    {
        // Step 1: Embed the query
        $embedding = $this->embed($query);

        // Step 2: Retrieve top documents
        $docs = $this->retrieveRelevantDocuments($embedding);

        // $docs = $this->retrieveRelevantDocuments($embedding);

        if ($docs->isEmpty()) {
            // No relevant docs found, just ask Ollama without context
            return $this->askOllama("", $query);
        }

        // Step 3: Build context block
        $context = $this->buildContext($docs);

        // Step 4: Ask Ollama
        return $this->askOllama($context, $query);
    }
}
