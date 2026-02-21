<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/question', function (Request $request) {
    $validated = $request->validate([
        'question' => ['required', 'string', 'max:1000'],
    ]);

    if (blank(config('services.openai.api_key'))) {
        return back()
            ->withInput()
            ->with('error', 'OpenAI API ключ не настроен. Добавьте OPENAI_API_KEY в .env.');
    }

    $response = Http::withToken(config('services.openai.api_key'))
        ->timeout(30)
        ->post('https://api.openai.com/v1/responses', [
            'model' => config('services.openai.model', 'gpt-4o-mini'),
            'input' => $validated['question'],
        ]);

    if ($response->failed()) {
        return back()
            ->withInput()
            ->with('error', 'Не удалось получить ответ от OpenAI API. Попробуйте позже.');
    }

    $answer = $response->json('output.0.content.0.text')
        ?? 'OpenAI API вернул ответ в неожиданном формате.';

    return back()
        ->with('success', 'Вопрос отправлен в OpenAI API.')
        ->with('answer', $answer)
        ->withInput();
})->name('question.submit');
