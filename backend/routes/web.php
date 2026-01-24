<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/__debug/cookies', function (Request $request) {
    return response()->json([
        'host' => $request->getHost(),
        'session_driver' => config('session.driver'),
        'has_session_id' => $request->hasSession(),
        'session_id' => session()->getId(),
        'cookie_header' => $request->headers->get('cookie'),
        'auth_check' => auth()->check(),
    ]);
});

require __DIR__.'/auth.php';
