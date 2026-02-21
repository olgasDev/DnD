<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/question', function (Request $request) {
    $validated = $request->validate([
        'question' => ['required', 'string', 'max:1000'],
    ]);

    return back()->with('success', 'Вопрос отправлен: ' . $validated['question']);
})->name('question.submit');
