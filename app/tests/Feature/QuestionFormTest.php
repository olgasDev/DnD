<?php

namespace Tests\Feature;

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

    public function test_question_can_be_submitted(): void
    {
        $response = $this->post('/question', [
            'question' => 'Это тестовый вопрос?',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');
    }

    public function test_question_is_required(): void
    {
        $response = $this->from('/')->post('/question', [
            'question' => '',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('question');
    }
}
