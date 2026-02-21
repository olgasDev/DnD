<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuestionFormTest extends TestCase
{
    public function test_home_page_shows_question_form(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Задайте вопрос');
        $response->assertSee('name="question"', false);
    }

    public function test_question_can_be_submitted_and_answer_is_shown(): void
    {
        Config::set('services.openai.api_key', 'test-key');

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output' => [
                    [
                        'content' => [
                            ['text' => 'Это ответ от OpenAI'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->post('/question', [
            'question' => 'Это тестовый вопрос?',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');
        $response->assertSessionHas('answer', 'Это ответ от OpenAI');
    }

    public function test_question_is_required(): void
    {
        $response = $this->from('/')->post('/question', [
            'question' => '',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('question');
    }

    public function test_error_is_returned_when_openai_key_is_missing(): void
    {
        Config::set('services.openai.api_key', null);

        $response = $this->from('/')->post('/question', [
            'question' => 'Вопрос без ключа',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }
}
