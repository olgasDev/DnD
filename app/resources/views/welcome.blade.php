<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Чаты OpenAI</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fb; margin: 0; }
        .layout { max-width: 1100px; margin: 24px auto; padding: 0 16px; display: grid; grid-template-columns: 320px 1fr; gap: 16px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 12px 30px rgba(0,0,0,.08); padding: 16px; }
        h1, h2, h3 { margin-top: 0; }
        input, textarea, button { width: 100%; box-sizing: border-box; border-radius: 8px; font-size: 14px; }
        input, textarea { border: 1px solid #d0d7e2; padding: 10px; margin: 8px 0; }
        textarea { min-height: 90px; resize: vertical; }
        button { border: 0; background: #2563eb; color: #fff; padding: 10px 12px; cursor: pointer; }
        .btn-danger { background: #dc2626; }
        .message { margin-bottom: 12px; padding: 10px 12px; border-radius: 8px; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
        .chat-list-item { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; margin-bottom: 10px; }
        .chat-list-item.active { border-color: #2563eb; background: #eff6ff; }
        .chat-list-item a { text-decoration: none; color: #111827; font-weight: 600; display: block; margin-bottom: 8px; }
        .history { max-height: 420px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; background: #f8fafc; margin-bottom: 12px; }
        .history-item { margin-bottom: 12px; white-space: pre-wrap; }
        .history-item:last-child { margin-bottom: 0; }
        .meta { color: #6b7280; font-size: 12px; margin-bottom: 8px; }
    </style>
</head>
<body>
<div class="layout">
    <aside class="card">
        <h2>Создать чат</h2>
        <form method="POST" action="{{ route('chats.store') }}">
            @csrf
            <label for="title">Название</label>
            <input id="title" name="title" required value="{{ old('title') }}" placeholder="Например: Подготовка к игре">

            <label for="system_prompt">System prompt</label>
            <textarea id="system_prompt" name="system_prompt" placeholder="Инструкции для ассистента в этом чате">{{ old('system_prompt') }}</textarea>

            <button type="submit">Создать</button>
        </form>

        <h3 style="margin-top: 18px;">Чаты</h3>
        @forelse ($chats as $chat)
            <div class="chat-list-item {{ $selectedChat && $selectedChat->id === $chat->id ? 'active' : '' }}">
                <a href="{{ route('chats.index', ['chat' => $chat->id]) }}">{{ $chat->title }}</a>
                <form method="POST" action="{{ route('chats.destroy', $chat) }}" onsubmit="return confirm('Удалить чат?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">Удалить чат</button>
                </form>
            </div>
        @empty
            <p>Пока нет чатов.</p>
        @endforelse
    </aside>

    <main class="card">
        <h1>История чата</h1>

        @if (session('success'))
            <div class="message success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="message error">{{ session('error') }}</div>
        @endif

        @error('title')<div class="message error">{{ $message }}</div>@enderror
        @error('system_prompt')<div class="message error">{{ $message }}</div>@enderror
        @error('question')<div class="message error">{{ $message }}</div>@enderror

        @if (! $selectedChat)
            <p>Создайте чат и выберите его, чтобы начать диалог.</p>
        @else
            <div class="meta"><strong>Чат:</strong> {{ $selectedChat->title }}</div>
            @if (filled($selectedChat->system_prompt))
                <div class="meta"><strong>System prompt:</strong> {{ $selectedChat->system_prompt }}</div>
            @endif

            <section class="history">
                @forelse ($selectedChat->messages as $message)
                    <p class="history-item">
                        <strong>{{ $message->role === 'assistant' ? 'OpenAI' : 'Вы' }}:</strong>
                        {{ $message->content }}
                    </p>
                @empty
                    <p class="history-item">Сообщений пока нет.</p>
                @endforelse
            </section>

            <form method="POST" action="{{ route('question.submit', $selectedChat) }}">
                @csrf
                <label for="question">Ваш вопрос</label>
                <textarea id="question" name="question" placeholder="Введите ваш вопрос..." required>{{ old('question') }}</textarea>
                <button type="submit">Отправить</button>
            </form>
        @endif
    </main>
</div>
</body>
</html>
