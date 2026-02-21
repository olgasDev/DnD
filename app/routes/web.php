<?php

use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $chats = Chat::query()->latest()->get();

    $selectedChat = null;

    if ($request->filled('chat')) {
        $selectedChat = Chat::query()->with('messages')->find($request->integer('chat'));
    }

    if (! $selectedChat && $chats->isNotEmpty()) {
        $selectedChat = Chat::query()->with('messages')->find($chats->first()->id);
    }

    return view('welcome', [
        'chats' => $chats,
        'selectedChat' => $selectedChat,
    ]);
})->name('chats.index');

Route::post('/chats', function (Request $request) {
    $validated = $request->validate([
        'title' => ['required', 'string', 'max:255'],
        'system_prompt' => ['nullable', 'string', 'max:2000'],
    ]);

    $chat = Chat::query()->create($validated);

    return redirect()->route('chats.index', ['chat' => $chat->id])
        ->with('success', 'Чат создан.');
})->name('chats.store');

Route::delete('/chats/{chat}', function (Chat $chat) {
    $chat->delete();

    return redirect()->route('chats.index')
        ->with('success', 'Чат удалён.');
})->name('chats.destroy');

Route::post('/chats/{chat}/question', function (Request $request, Chat $chat) {
    $validated = $request->validate([
        'question' => ['required', 'string', 'max:1000'],
    ]);

    if (blank(config('services.openai.api_key'))) {
        return redirect()->route('chats.index', ['chat' => $chat->id])
            ->withInput()
            ->with('error', 'OpenAI API ключ не настроен. Добавьте OPENAI_API_KEY в .env.');
    }

    $chat->load('messages');

    $messages = [];

    if (filled($chat->system_prompt)) {
        $messages[] = [
            'role' => 'system',
            'content' => $chat->system_prompt,
        ];
    }

    foreach ($chat->messages as $message) {
        $messages[] = [
            'role' => $message->role,
            'content' => $message->content,
        ];
    }

    $messages[] = [
        'role' => 'user',
        'content' => $validated['question'],
    ];

    $response = Http::withToken(config('services.openai.api_key'))
        ->timeout(30)
        ->post('https://api.openai.com/v1/chat/completions', [
            'model' => config('services.openai.model', 'gpt-4o-mini'),
            'messages' => $messages,
        ]);

    if ($response->failed()) {
        return redirect()->route('chats.index', ['chat' => $chat->id])
            ->withInput()
            ->with('error', 'Не удалось получить ответ от OpenAI API. Попробуйте позже.');
    }

    $answer = $response->json('choices.0.message.content')
        ?? 'OpenAI API вернул ответ в неожиданном формате.';

    $chat->messages()->createMany([
        [
            'role' => 'user',
            'content' => $validated['question'],
        ],
        [
            'role' => 'assistant',
            'content' => $answer,
        ],
    ]);

    return redirect()->route('chats.index', ['chat' => $chat->id])
        ->with('success', 'Вопрос отправлен в OpenAI API.')
        ->with('answer', $answer);
})->name('question.submit');
