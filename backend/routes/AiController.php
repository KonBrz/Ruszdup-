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

        $userMessage = $validated['prompt'];
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            return response()->json([
                'response' => 'Brak klucza API. Skonfiguruj GEMINI_API_KEY w pliku .env.'
            ], 500);
        }

        // Wywołanie Google Gemini API (przykładowa integracja)
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    ['parts' => [['text' => "Jesteś ekspertem od planowania podróży. Doradź użytkownikowi: " . $userMessage]]]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'response' => 'Błąd podczas łączenia z API zewnętrznym: ' . $e->getMessage()
            ], 502);
        }

        if ($response->successful()) {
            $data = $response->json();
            $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'AI nie zwróciło odpowiedzi.';
        } else {
            $aiResponse = 'Wystąpił błąd połączenia z AI (' . $response->status() . ').';
        }

        return response()->json([
            'response' => $aiResponse,
        ]);
    }
}
