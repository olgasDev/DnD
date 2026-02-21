<?php

namespace Tests\Feature;

use App\Models\Chat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuestionFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_chat_creation_form(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Создать чат');
        $response->assertSee('name="title"', false);
        $response->assertSee('name="system_prompt"', false);
    }

    public function test_chat_can_be_created_with_system_prompt(): void
    {
        $response = $this->post('/chats', [
            'title' => 'Новый чат',
            'system_prompt' => 'Ты DM по DnD',
        ]);

        $chat = Chat::query()->first();

        $response->assertRedirect('/?chat='.$chat->id);
        $this->assertDatabaseHas('chats', [
            'title' => 'Новый чат',
            'system_prompt' => 'Ты DM по DnD',
        ]);
    }

    public function test_chat_can_be_deleted_with_messages(): void
    {
        $chat = Chat::query()->create([
            'title' => 'Временный',
            'system_prompt' => null,
        ]);

        $chat->messages()->create([
            'role' => 'user',
            'content' => 'Тест',
        ]);

        $response = $this->delete('/chats/'.$chat->id);

        $response->assertRedirect('/');
        $this->assertDatabaseMissing('chats', ['id' => $chat->id]);
        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_question_is_saved_and_answer_is_saved_in_chat_history(): void
    {
        Config::set('services.openai.api_key', 'test-key');

        $chat = Chat::query()->create([
            'title' => 'Игра',
            'system_prompt' => 'Ты помощник',
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Это ответ от OpenAI']],
                ],
            ], 200),
        ]);

        $response = $this->post('/chats/'.$chat->id.'/question', [
            'question' => 'Это тестовый вопрос?',
        ]);

        $response->assertRedirect('/?chat='.$chat->id);
        $this->assertDatabaseHas('chat_messages', [
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => 'Это тестовый вопрос?',
        ]);
        $this->assertDatabaseHas('chat_messages', [
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Это ответ от OpenAI',
        ]);
    }

    public function test_history_and_system_prompt_are_sent_to_openai(): void
    {
        Config::set('services.openai.api_key', 'test-key');

        $chat = Chat::query()->create([
            'title' => 'Лор',
            'system_prompt' => 'Говори как мудрый маг',
        ]);

        $chat->messages()->createMany([
            ['role' => 'user', 'content' => 'Кто ты?'],
            ['role' => 'assistant', 'content' => 'Я маг.'],
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Новый ответ']],
                ],
            ], 200),
        ]);

        $this->post('/chats/'.$chat->id.'/question', ['question' => 'Что ты умеешь?']);

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://api.openai.com/v1/chat/completions') {
                return false;
            }

            $messages = $request['messages'];

            return count($messages) === 4
                && $messages[0]['role'] === 'system'
                && $messages[0]['content'] === 'Говори как мудрый маг'
                && $messages[1]['role'] === 'user'
                && $messages[1]['content'] === 'Кто ты?'
                && $messages[2]['role'] === 'assistant'
                && $messages[2]['content'] === 'Я маг.'
                && $messages[3]['role'] === 'user'
                && $messages[3]['content'] === 'Что ты умеешь?';
        });
    }
}
