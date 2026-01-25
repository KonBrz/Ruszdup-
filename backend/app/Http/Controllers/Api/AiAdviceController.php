<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAdviceController extends Controller
{
    public function getAdvice(Request $request)
    {
        $validated = $request->validate([
            'destination' => 'required|string',
        ]);

        $destination = $validated['destination'];
        $apiKey = config('services.gemini.key');
        $isFake = filter_var(env('GEMINI_FAKE'), FILTER_VALIDATE_BOOLEAN);

        if ($isFake || !$apiKey) {
            return response()->json([
                'advice' => 'Spakuj dokumenty, sprawdz pogode i zaplanuj kluczowe atrakcje.'
            ]);
        }

        $model = 'gemini-2.5-flash';
        $promptText = "Odpowiedz krótko (1-2 zdania). Podaj praktyczną poradę podróżniczą dla miasta {$destination}.";

        $response = Http::post("https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}", [
            'contents' => [
                ['parts' => [['text' => $promptText]]]
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $aiResponse = data_get($data, 'candidates.0.content.parts.0.text', 'Brak odpowiedzi od AI.');

            return response()->json([
                'advice' => $aiResponse
            ]);
        }

        Log::warning('AI advice API error', ['status' => $response->status(), 'body' => $response->body()]);

        return response()->json([
            'advice' => 'Wystąpił błąd połączenia z AI (' . $response->status() . ').',
            'ai_error_status' => $response->status(),
        ], 500);
    }
}
