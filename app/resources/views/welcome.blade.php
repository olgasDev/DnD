<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Форма вопроса</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
        }

        .card {
            width: min(560px, 92vw);
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
            padding: 24px;
        }

        h1 {
            margin-top: 0;
            font-size: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        textarea {
            width: 100%;
            min-height: 120px;
            border: 1px solid #d0d7e2;
            border-radius: 8px;
            padding: 12px;
            font-size: 16px;
            resize: vertical;
            box-sizing: border-box;
        }

        button {
            margin-top: 12px;
            background: #2563eb;
            color: #fff;
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 16px;
            cursor: pointer;
        }

        .message {
            margin-bottom: 12px;
            padding: 10px 12px;
            border-radius: 8px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>Задайте вопрос</h1>

        @if (session('success'))
            <div class="message success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="message error">{{ session('error') }}</div>
        @endif

        @error('question')
            <div class="message error">{{ $message }}</div>
        @enderror

        @if (session('answer'))
            <section class="message success">
                <strong>Ответ OpenAI:</strong>
                <p style="margin: 8px 0 0; white-space: pre-wrap;">{{ session('answer') }}</p>
            </section>
        @endif

        <form method="POST" action="{{ route('question.submit') }}">
            @csrf
            <label for="question">Ваш вопрос</label>
            <textarea id="question" name="question" placeholder="Введите ваш вопрос..." required>{{ old('question') }}</textarea>
            <button type="submit">Отправить</button>
        </form>
    </main>
</body>
</html>
