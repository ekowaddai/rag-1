<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Http;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::post('/chat', 'App\Http\Controllers\ChatController');


Route::get('/test-ollama', function () {
    $prompt = "Hi! Please respond to test if you are working.";

    $response = Http::post('http://localhost:11434/api/generate', [
        'model' => 'llama3.2:1b',
        'prompt' => $prompt,
        'stream' => false
    ]);

    if ($response->failed()) {
        return response()->json([
            'status' => 'failed',
            'error' => $response->json()
        ], 500);
    }

    return response()->json([
        'status' => 'success',
        'prompt' => $prompt,
        'response' => $response->json()['response'] ?? null
    ]);
});


use Illuminate\Support\Facades\DB;

Route::get('/test-rag-old', function () {

    // Hardcoded test query
    $query = "What are the stages of photosynthesis?";

    // Step 1: Generate embedding for query
    $embeddingResponse = Http::post('http://localhost:11434/api/embeddings', [
        'model' => 'llama3.2:1b',
        'prompt' => $query
    ]);

    if ($embeddingResponse->failed()) {
        return response()->json(['error' => 'Failed to generate embedding'], 500);
    }

    $queryEmbedding = $embeddingResponse->json()['embedding']; // array of floats

    // Step 2: Retrieve top 3 most similar documents
    $scoreParts = [];
    foreach ($queryEmbedding as $i => $val) {
        $scoreParts[] = "embedding->'\$[{$i}]' * ?";
    }
    $scoreSql = implode(' + ', $scoreParts);

    $documents = DB::table('documents')
        ->select('title', 'content')
        ->selectRaw("($scoreSql) AS score", $queryEmbedding)
        ->orderByDesc('score')
        ->limit(3)
        ->get();

    // Step 3: Build context string
    $context = "";
    foreach ($documents as $doc) {
        $context .= "Title: {$doc->title}\nContent: {$doc->content}\n\n";
    }

    // Step 4: Build prompt for Ollama
    $finalPrompt = "Use ONLY the context below to answer the question.\n\n".
                   "CONTEXT:\n{$context}\n\n".
                   "QUESTION: {$query}\nANSWER:";
    

    /* Forcing it to use the context
    $finalPrompt = "You are a helpful assistant. Answer the question using ONLY the context below. " .
               "Do NOT use any prior knowledge outside of this context.\n\n" .
               "CONTEXT:\n{$context}\n\n" .
               "QUESTION: {$query}\n" .
               "ANSWER:";
    */
    // Step 5: Send to Ollama
    $response = Http::post('http://localhost:11434/api/generate', [
        'model' => 'llama3.2:1b',
        'prompt' => $finalPrompt,
        'stream' => false
    ]);

    if ($response->failed()) {
        return response()->json(['error' => 'Ollama request failed'], 500);
    }

    return response()->json([
        'query' => $query,
        'retrieved_documents' => $documents,
        'response' => $response->json()['response'] ?? null
    ]);
});




Route::get('/test-rag', function () {

    // 1️⃣ Hardcoded test query
    $query = "What are the stages of photosynthesis?";

    // 2️⃣ Generate embedding for query via Ollama
    $embeddingResponse = Http::post('http://localhost:11434/api/embeddings', [
        'model' => 'llama3.2:1b',
        'prompt' => $query
    ]);

    if ($embeddingResponse->failed()) {
        return response()->json(['error' => 'Failed to generate embedding'], 500);
    }

    $queryEmbedding = $embeddingResponse->json()['embedding']; // array of floats

    // 3️⃣ Retrieve all documents from DB
    $documents = DB::table('documents')->get();

    // 4️⃣ Compute dot product similarity in PHP
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

    // 5️⃣ Sort by score descending and pick top 3
    $topDocs = collect($documents)
        ->sortByDesc('score')
        ->take(3);

    // 6️⃣ Build context string
    $context = "";
    foreach ($topDocs as $doc) {
        $context .= "Title: {$doc->title}\nContent: {$doc->content}\n\n";
    }

    // 7️⃣ Build final prompt for Ollama
    $finalPrompt = "Use ONLY the context below to answer the question.\n\n".
                   "CONTEXT:\n{$context}\n\n".
                   "QUESTION: {$query}\nANSWER:";

    // 8️⃣ Send to Ollama
    $response = Http::post('http://localhost:11434/api/generate', [
        'model' => 'llama3.2:1b',
        'prompt' => $finalPrompt,
        'stream' => false
    ]);

    if ($response->failed()) {
        return response()->json(['error' => 'Ollama request failed'], 500);
    }

    return response()->json([
        'query' => $query,
        'retrieved_documents' => $topDocs,
        'response' => $response->json()['response'] ?? null
    ]);
});
