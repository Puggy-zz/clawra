<?php

declare(strict_types=1);

use App\Agents\SimpleChatAgent;

it('uses the synthetic simple chat agent test route', function () {
    SimpleChatAgent::fake(['Synthetic hello'])->preventStrayPrompts();

    $response = $this->getJson('/test-agent');

    $response->assertSuccessful()
        ->assertJson([
            'status' => 'success',
            'response' => 'Synthetic hello',
        ]);

    SimpleChatAgent::assertPrompted('Hello, how are you?');
});
