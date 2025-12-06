<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/trip-invite/accept', function () {
    $token = request('token');

    if (!$token) {
        abort(404, 'Brak tokena.');
    }

    // Jeśli user niezalogowany → przekieruj na login i ZACHOWAJ TOKEN
    if (!auth()->check()) {
        session(['invite_token' => $token]);
        return redirect('/login/' . $token);
    }

    // Jeśli user zalogowany → od razu obsłuż w API (kontroler)
    return redirect('/api/trip-invite/accept?token=' . $token);
});

require __DIR__.'/auth.php';
