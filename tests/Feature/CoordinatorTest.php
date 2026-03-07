<?php

declare(strict_types=1);

use Illuminate\Testing\TestResponse;

// Test that the coordinator page loads
it('can display the coordinator interface', function () {
    $response = $this->get('/');
    
    $response->assertStatus(200);
    $response->assertSee('Clawra Coordinator');
});

// Test that the coordinator can process messages
it('can process messages', function () {
    $response = $this->postJson('/coordinator/message', [
        'message' => 'Hello, Coordinator!'
    ]);
    
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'status',
        'response',
        'timestamp'
    ]);
    
    $response->assertJson([
        'status' => 'success'
    ]);
    
    $responseData = $response->json();
    expect($responseData['response'])->toBeString();
});

// Test validation for empty messages
it('rejects empty messages', function () {
    $response = $this->postJson('/coordinator/message', [
        'message' => ''
    ]);
    
    $response->assertStatus(422);
});

// Test validation for messages that are too long
it('rejects messages that are too long', function () {
    $response = $this->postJson('/coordinator/message', [
        'message' => str_repeat('a', 1001)
    ]);
    
    $response->assertStatus(422);
});