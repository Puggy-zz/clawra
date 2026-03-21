<?php

declare(strict_types=1);

use App\Jobs\ProcessChatMessageJob;
use App\Livewire\Chat;
use App\Models\Project;
use App\Services\ProjectConversationService;
use App\Services\ProjectService;
use Database\Seeders\AgentSeeder;
use Database\Seeders\ProviderSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);
});

// --- Mount ---

it('mounts and resolves a default project and conversation', function () {
    Project::factory()->create(['name' => 'Inbox']);

    $component = Livewire::test(Chat::class);

    expect($component->get('activeProjectId'))->not->toBeNull()
        ->and($component->get('activeConversationId'))->not->toBeNull();
});

it('renders the chat view without errors', function () {
    Project::factory()->create();

    Livewire::test(Chat::class)
        ->assertViewIs('livewire.chat')
        ->assertStatus(200);
});

// --- Send message ---

it('sends a message, clears the input, and dispatches a job', function () {
    Queue::fake();
    Project::factory()->create();

    Livewire::test(Chat::class)
        ->set('message', 'What should I work on today?')
        ->call('sendMessage')
        ->assertSet('message', '')
        ->assertHasNoErrors();

    Queue::assertPushed(ProcessChatMessageJob::class, function ($job) {
        return $job->message === 'What should I work on today?';
    });
});

it('stores the pending message immediately so it shows while the job runs', function () {
    Queue::fake();
    $conversationService = app(ProjectConversationService::class);
    $project = app(ProjectService::class)->ensureInboxProject();
    $conversation = $conversationService->ensureDefaultConversation($project);

    Livewire::test(Chat::class)
        ->set('activeProjectId', $project->id)
        ->set('activeConversationId', $conversation->id)
        ->set('message', 'Plan the sprint.')
        ->call('sendMessage');

    $messages = $conversationService->getMessages($conversation->fresh());

    expect($messages)->toHaveCount(1)
        ->and($messages[0]['role'])->toBe('user')
        ->and($messages[0]['content'])->toBe('Plan the sprint.');
});

it('does nothing when a blank message is submitted', function () {
    Queue::fake();
    Project::factory()->create();

    Livewire::test(Chat::class)
        ->set('message', '   ')
        ->call('sendMessage')
        ->assertSet('errorMessage', null);

    Queue::assertNothingPushed();
});

it('fails validation when message exceeds 1000 characters', function () {
    Queue::fake();
    Project::factory()->create();

    Livewire::test(Chat::class)
        ->set('message', str_repeat('a', 1001))
        ->call('sendMessage')
        ->assertHasErrors(['message' => 'max']);
});

// --- Project switching ---

it('resets activeConversationId when activeProjectId changes', function () {
    $service = app(ProjectConversationService::class);

    $project1 = Project::factory()->create(['name' => 'Alpha']);
    $project2 = Project::factory()->create(['name' => 'Beta']);
    $conv1 = $service->ensureDefaultConversation($project1);
    $conv2 = $service->ensureDefaultConversation($project2);

    $component = Livewire::test(Chat::class)
        ->set('activeProjectId', $project1->id);

    $convAfterP1 = $component->get('activeConversationId');

    $component->set('activeProjectId', $project2->id);
    $convAfterP2 = $component->get('activeConversationId');

    expect($convAfterP1)->toBe($conv1->id)
        ->and($convAfterP2)->toBe($conv2->id)
        ->and($convAfterP1)->not->toBe($convAfterP2);
});

// --- Processing state ---

it('shows the Thinking indicator while is_processing is true', function () {
    $conversationService = app(ProjectConversationService::class);
    $project = app(ProjectService::class)->ensureInboxProject();
    $conversation = $conversationService->ensureDefaultConversation($project);
    $conversationService->storePendingMessage($conversation, 'What is the plan?');

    Livewire::test(Chat::class)
        ->set('activeProjectId', $project->id)
        ->set('activeConversationId', $conversation->id)
        ->assertSee('Thinking')
        ->assertSee('What is the plan?');
});
