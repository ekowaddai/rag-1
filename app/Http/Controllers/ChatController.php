<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RagService;

class ChatController extends Controller
{
    public function __invoke(Request $request, RagService $ragService)
    {
        $prompt = $request->post('content');

        try {
            $answer = $ragService->answerWithRag($prompt);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }

        return $answer;
    }
}
