<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function chat(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string',
        ]);

        // Tutaj w przyszłości dodasz integrację z OpenAI (np. ChatGPT)
        // Na razie zwracamy symulowaną odpowiedź, aby przetestować frontend
        
        $userMessage = $validated['prompt'];
        
        // Symulacja odpowiedzi AI
        $aiResponse = "To jest przykładowa odpowiedź z serwera na Twoje pytanie: \"{$userMessage}\". Tutaj wkrótce pojawi się prawdziwa AI.";

        return response()->json([
            'response' => $aiResponse
        ]);
    }
}