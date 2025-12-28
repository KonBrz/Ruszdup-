<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    public function chat(Request $request)
    {
        Log::info('AI chat incoming request', ['content' => $request->getContent(), 'headers' => $request->headers->all(), 'method' => $request->method()]);

        // Only allow authenticated users to use the AI chat
        // Sprawdzamy użytkownika przez guard sanctum, bo wyjęliśmy trasę z middleware
        if (!auth('sanctum')->user()) {
            return response()->json([
                'response' => 'Zaloguj się aby porozmawiać z botem'
            ], 401);
        }

        $validated = $request->validate([
            'prompt' => 'required|string',
        ]);

        $userMessage = $validated['prompt'];
        $apiKey = config('services.gemini.key'); // Zmieniono env() na config() dla stabilności cache

        if (!$apiKey) {
            return response()->json([
                'response' => 'Brak klucza API. Skonfiguruj GEMINI_API_KEY w pliku .env.'
            ], 500); // Zmieniono kod statusu na 500 (błąd serwera)
        }
        // Dodajemy kontekst, że AI jest doradcą podróży
        // Use available model from ListModels (e.g. gemini-2.5-flash) and v1 API
        $model = 'gemini-2.5-flash';
        // Configure for short, concrete answers: low temperature, limited tokens
        $promptText = "Odpowiedz krótko (maks. 2 zdania). Jesteś ekspertem od planowania podróży. Doradź użytkownikowi: " . $userMessage;

        // Only send supported payload (contents). Control brevity via the prompt.
        $response = Http::post("https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}", [
            'contents' => [
                ['parts' => [['text' => $promptText]]]
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            // Użycie data_get() do bezpiecznego dostępu do zagnieżdżonych danych
            $aiResponse = data_get($data, 'candidates.0.content.parts.0.text', 'AI nie zwróciło odpowiedzi lub odpowiedź została zablokowana.');

            return response()->json([
                'response' => $aiResponse
            ]);
        }

        // Debug: zwróć status i treść odpowiedzi z API (nie ujawniaj klucza)
        $body = $response->body();
        // Logujemy pełną odpowiedź dla dalszej analizy
        Log::warning('AI API error', ['status' => $response->status(), 'body' => $body]);

        return response()->json([
            'response' => 'Wystąpił błąd połączenia z AI (' . $response->status() . ').',
            'ai_error_status' => $response->status(),
            // Usunięto zwracanie treści błędu do klienta ze względów bezpieczeństwa
        ], 500);
    }

    public function ask(Request $request)
    {
        if (!auth('sanctum')->user()) {
            return response()->json([
                'response' => 'Zaloguj się aby skorzystać z pomocy AI.'
            ], 401);
        }

        $validated = $request->validate([
            'destination' => 'required|string',
        ]);

        $destination = $validated['destination'];
        $apiKey = config('services.gemini.key');

        if (!$apiKey) {
            return response()->json([
                'response' => 'Brak klucza API. Skonfiguruj GEMINI_API_KEY w pliku .env.'
            ], 500);
        }

        $model = 'gemini-1.5-pro-latest';
        $promptText = "Jesteś ekspertem od planowania podróży. Podaj 5 propozycji zadań do wykonania przed wyjazdem do miasta {$destination}. Odpowiedź w formie listy, krótko i zwięźle.";

        $response = Http::post("https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}", [
            'contents' => [
                ['parts' => [['text' => $promptText]]]
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $aiResponse = data_get($data, 'candidates.0.content.parts.0.text', 'AI nie zwróciło odpowiedzi lub odpowiedź została zablokowana.');

            return response()->json([
                'suggestions' => $aiResponse
            ]);
        }

        Log::warning('AI API error', ['status' => $response->status(), 'body' => $response->body()]);

        return response()->json([
            'response' => 'Wystąpił błąd połączenia z AI (' . $response->status() . ').',
            'ai_error_status' => $response->status(),
        ], 500);
    }
}
