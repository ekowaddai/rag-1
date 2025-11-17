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
     * Retrieve top N relevant documents from DB using PHP similarity
     */
    public function retrieveRelevantDocuments(array $queryEmbedding, int $limit = 3)
    {
        $documents = DB::table('documents')->get();

        // Compute dot product similarity in PHP
        $dotProduct = function(array $vec1, array $vec2) {
            $sum = 0;
            foreach ($vec1 as $i => $v) {
                $sum += $v * ($vec2[$i] ?? 0);
            }
            return $sum;
        };

        foreach ($documents as $doc) {
            $docEmbedding = json_decode($doc->embedding, true);
            $doc->score = $dotProduct($docEmbedding, $queryEmbedding);
        }

        // Sort by score descending and pick top N
        return collect($documents)
            ->sortByDesc('score')
            ->take($limit);
    }

    /**
     * Build context string from documents
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
     * Ask Ollama using strict context-only prompt
     */
    public function askOllama(string $context, string $question): string
    {
        if (empty(trim($context))) {
            $context = "No relevant documents found.";
        }

        $finalPrompt = <<<EOD
You are a helpful assistant. Answer the question using ONLY the context below.
If the answer is not present in the context, respond with "I do not know."

CONTEXT START
{$context}
CONTEXT END

QUESTION: {$question}
ANSWER:
EOD;

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
     * Full RAG pipeline: embed → retrieve → answer
     */
    public function answerWithRag(string $query): string
    {
        $embedding = $this->embed($query);
        $documents = $this->retrieveRelevantDocuments($embedding);
        $context = $this->buildContext($documents);
        return $this->askOllama($context, $query);
    }
}
