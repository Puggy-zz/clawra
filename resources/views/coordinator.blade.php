<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Clawra Coordinator</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            :root {
                --bg: #f4f1ea;
                --panel: rgba(255, 253, 248, 0.95);
                --border: #d8cdbd;
                --text: #1f1c17;
                --muted: #6f675d;
                --accent: #345c69;
                --accent-soft: #dce9ed;
                --success: #2d6a4f;
                --warning: #9a6700;
                --danger: #8a3b2f;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                color: var(--text);
                background:
                    radial-gradient(circle at top left, rgba(52, 92, 105, 0.18), transparent 28%),
                    radial-gradient(circle at top right, rgba(166, 123, 91, 0.16), transparent 24%),
                    linear-gradient(180deg, #f8f4ee 0%, var(--bg) 100%);
            }

            .shell {
                max-width: 1440px;
                margin: 0 auto;
                padding: 28px 22px 44px;
            }

            .hero {
                display: flex;
                justify-content: space-between;
                gap: 18px;
                align-items: end;
                margin-bottom: 20px;
            }

            .hero h1 {
                margin: 0;
                font-size: 2.15rem;
                letter-spacing: -0.04em;
            }

            .hero p {
                margin: 8px 0 0;
                max-width: 760px;
                color: var(--muted);
                line-height: 1.45;
            }

            .hero-actions {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }

            .hero-actions form {
                margin: 0;
            }

            .layout {
                display: grid;
                grid-template-columns: minmax(0, 1.55fr) minmax(320px, 0.95fr);
                gap: 18px;
            }

            .stack {
                display: grid;
                gap: 18px;
            }

            .panel {
                background: var(--panel);
                border: 1px solid var(--border);
                border-radius: 18px;
                box-shadow: 0 14px 30px rgba(31, 28, 23, 0.06);
                overflow: hidden;
            }

            .panel-header {
                padding: 16px 18px 10px;
                display: flex;
                justify-content: space-between;
                gap: 14px;
                align-items: start;
            }

            .panel-header h2,
            .panel-header h3 {
                margin: 0;
                font-size: 1rem;
            }

            .panel-header p {
                margin: 6px 0 0;
                color: var(--muted);
                font-size: 0.9rem;
                line-height: 1.35;
            }

            .panel-body {
                padding: 0 18px 18px;
            }

            .chat-body {
                display: grid;
                grid-template-rows: 1fr auto;
                min-height: 460px;
            }

            .chat-messages {
                padding: 0 18px 14px;
                overflow-y: auto;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .message {
                padding: 12px 14px;
                border-radius: 14px;
                max-width: 88%;
            }

            .user-message {
                align-self: end;
                background: #efe4d8;
            }

            .assistant-message {
                align-self: start;
                background: var(--accent-soft);
            }

            .message-header {
                margin-bottom: 5px;
                font-size: 0.77rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }

            .message-content {
                line-height: 1.45;
            }

            .message-badge {
                display: inline-flex;
                margin-top: 8px;
                padding: 4px 9px;
                border-radius: 999px;
                background: rgba(52, 92, 105, 0.1);
                color: var(--accent);
                font-size: 0.74rem;
                font-weight: 700;
            }

            .input-container {
                border-top: 1px solid var(--border);
                padding: 14px 18px 12px;
                display: flex;
                gap: 10px;
            }

            .status-line {
                padding: 0 18px 16px;
                color: var(--muted);
                font-size: 0.88rem;
            }

            input,
            textarea,
            select,
            button,
            summary {
                font: inherit;
            }

            input,
            textarea,
            select {
                width: 100%;
                border: 1px solid var(--border);
                border-radius: 10px;
                padding: 9px 11px;
                background: #fff;
                color: var(--text);
            }

            textarea {
                min-height: 76px;
                resize: vertical;
            }

            button,
            .button-link,
            summary.toggle-summary {
                border: 0;
                border-radius: 10px;
                background: var(--accent);
                color: #fff;
                font-weight: 700;
                cursor: pointer;
                text-decoration: none;
            }

            button,
            .button-link {
                padding: 9px 13px;
            }

            button.secondary,
            .button-link.secondary,
            summary.toggle-summary {
                background: #5a5147;
            }

            button.tiny,
            .button-link.tiny,
            summary.toggle-summary {
                padding: 6px 10px;
                font-size: 0.79rem;
                line-height: 1;
            }

            button.danger,
            .button-link.danger {
                background: var(--danger);
            }

            .section-actions {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }

            .form-grid {
                display: grid;
                gap: 10px;
            }

            .form-grid.two {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .cards {
                display: grid;
                gap: 10px;
            }

            .empty {
                color: var(--muted);
                font-size: 0.92rem;
            }

            .compact-card {
                border: 1px solid var(--border);
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.7);
                overflow: hidden;
            }

            .compact-card[open] {
                background: rgba(255, 255, 255, 0.92);
            }

            .compact-summary {
                list-style: none;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 10px;
                align-items: center;
                padding: 12px 14px;
                cursor: pointer;
            }

            .compact-summary::-webkit-details-marker {
                display: none;
            }

            .compact-title {
                display: grid;
                gap: 4px;
            }

            .compact-title strong {
                font-size: 0.95rem;
            }

            .meta {
                color: var(--muted);
                font-size: 0.82rem;
                line-height: 1.35;
            }

            .pill-row {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }

            .pill {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: 4px 8px;
                background: #ede7dc;
                color: #5a5147;
                font-size: 0.72rem;
                font-weight: 700;
                line-height: 1;
            }

            .pill.active {
                background: rgba(45, 106, 79, 0.12);
                color: var(--success);
            }

            .pill.rate-limited {
                background: rgba(154, 103, 0, 0.12);
                color: var(--warning);
            }

            .card-actions {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                align-items: center;
            }

            .card-body {
                padding: 0 14px 14px;
                display: grid;
                gap: 12px;
                border-top: 1px solid var(--border);
            }

            .details-grid {
                display: grid;
                gap: 10px;
            }

            details.inline-toggle > summary {
                list-style: none;
            }

            details.inline-toggle > summary::-webkit-details-marker {
                display: none;
            }

            details.inline-toggle {
                display: grid;
                gap: 10px;
            }

            .toggle-summary {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: fit-content;
            }

            .quick-grid {
                display: grid;
                gap: 10px;
            }

            .catalog-grid {
                display: grid;
                gap: 10px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .catalog-card {
                border: 1px solid var(--border);
                border-radius: 14px;
                padding: 12px;
                background: rgba(255, 255, 255, 0.72);
                display: grid;
                gap: 8px;
            }

            .catalog-card h4 {
                margin: 0;
                font-size: 0.92rem;
            }

            .toolbar {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }

            @media (max-width: 1120px) {
                .layout {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 760px) {
                .shell {
                    padding: 20px 14px 30px;
                }

                .hero {
                    flex-direction: column;
                    align-items: start;
                }

                .form-grid.two,
                .catalog-grid {
                    grid-template-columns: 1fr;
                }

                .message,
                .input-container {
                    max-width: 100%;
                    flex-direction: column;
                }

                .compact-summary {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    @endif
</head>
<body>
    <div class="shell">
        <div class="hero">
            <div>
                <h1>Clawra Phase 0 Control Room</h1>
                <p>Track orchestration, keep agent and provider settings in sync with the database, and open deeper CRUD controls only when you need them.</p>
            </div>
            <div class="hero-actions">
                <form method="POST" action="/coordinator/heartbeat">
                    @csrf
                    <button class="tiny" type="submit">Run Heartbeat</button>
                </form>
            </div>
        </div>

        <div class="layout">
            <div class="stack">
                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h2>Coordinator Chat</h2>
                            <p>Each project can have multiple conversations. Start fresh threads for bug fixes, experiments, or intake without polluting the main thread.</p>
                        </div>
                        <div class="section-actions">
                            <form class="form-grid two" method="GET" action="/coordinator" style="min-width: 320px;">
                                <select name="project_id" onchange="this.form.submit()">
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}" @selected($activeProject->id === $project->id)>{{ $project->name }}</option>
                                    @endforeach
                                </select>
                                <select name="conversation_id" onchange="this.form.submit()">
                                    @foreach ($projectConversations as $conversationOption)
                                        <option value="{{ $conversationOption->id }}" @selected($activeConversation->id === $conversationOption->id)>{{ $conversationOption->title }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <details class="inline-toggle">
                                <summary class="toggle-summary tiny">New Conversation</summary>
                                <div class="panel-body">
                                    <form class="form-grid" method="POST" action="/coordinator/conversations">
                                        @csrf
                                        <input type="hidden" name="project_id" value="{{ $activeProject->id }}">
                                        <input type="text" name="title" placeholder="Conversation title" required>
                                        <input type="text" name="purpose" placeholder="Purpose, eg bugfix or experiment">
                                        <button type="submit">Create Conversation</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </div>
                    <div class="chat-body">
                        <div class="chat-messages" id="chat-messages">
                            @forelse ($conversationMessages as $conversationMessage)
                                <div class="message {{ $conversationMessage['role'] === 'user' ? 'user-message' : 'assistant-message' }}">
                                    <div class="message-header">{{ $conversationMessage['role'] === 'user' ? 'You' : 'Coordinator' }}</div>
                                    <div class="message-content">{{ $conversationMessage['content'] }}</div>
                                </div>
                            @empty
                                <div class="message assistant-message">
                                    <div class="message-header">Coordinator</div>
                                    <div class="message-content">Hello. This conversation starts fresh, but I know the project summary and available agents. Ask me to plan, research, or shape work into a task.</div>
                                </div>
                            @endforelse
                        </div>
                        <div>
                            <div class="input-container">
                                <input type="hidden" id="active-project-id" value="{{ $activeProject->id }}">
                                <input type="hidden" id="active-conversation-id" value="{{ $activeConversation->id }}">
                                <input type="text" id="user-input" placeholder="Ask Clawra to plan, research, or track the next task..." />
                                <button id="send-button" type="button">Send</button>
                            </div>
                            <div class="status-line">Project: <strong>{{ $activeProject->name }}</strong> · Conversation: <strong id="active-conversation-title">{{ $activeConversation->title }}</strong> · Status: <span id="status">Ready</span></div>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h3>Pending Draft</h3>
                            <p>The current task draft lives here until you confirm, revise, or cancel it.</p>
                        </div>
                    </div>
                    <div class="panel-body" id="pending-draft-panel">
                        @if ($pendingTaskDraft)
                            <div class="compact-card" id="pending-draft-card">
                                <div class="compact-summary">
                                    <div class="compact-title">
                                        <strong id="pending-draft-title">{{ $pendingTaskDraft['title'] ?? 'Untitled draft' }}</strong>
                                        <div class="meta" id="pending-draft-summary">{{ $pendingTaskDraft['summary'] ?? '' }}</div>
                                        <div class="pill-row">
                                            <span class="pill">{{ $pendingTaskDraft['workflow_type'] ?? 'general' }}</span>
                                            @if (! empty($pendingTaskDraft['recommended_agent']))
                                                <span class="pill active" id="pending-draft-agent">{{ $pendingTaskDraft['recommended_agent'] }}</span>
                                            @endif
                                            @if (($pendingTaskDraft['needs_clarification'] ?? false) === true)
                                                <span class="pill rate-limited">needs clarification</span>
                                            @else
                                                <span class="pill active">ready to confirm</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    @if (! empty($pendingTaskDraft['description']))
                                        <div class="meta">{{ $pendingTaskDraft['description'] }}</div>
                                    @endif
                                    @if (! empty($pendingTaskDraft['clarifying_questions']))
                                        <div class="details-grid">
                                            <div class="meta">Clarifying questions</div>
                                            @foreach ($pendingTaskDraft['clarifying_questions'] as $question)
                                                <div class="meta">- {{ $question }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="meta">Reply in chat with `confirm` to create this task, or describe what to revise.</div>
                                </div>
                            </div>
                        @else
                            <div class="empty" id="pending-draft-empty">No draft in progress. Ask Clawra about a task to begin refining one.</div>
                        @endif
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h3>Activity</h3>
                            <p>Append-only coordinator and runtime history, plus resumable external session references.</p>
                        </div>
                    </div>
                    <div class="panel-body" id="activity-panel">
                        @if ($processLogs->isEmpty())
                            <div class="empty">No orchestration activity logged yet.</div>
                        @else
                            <div class="cards" id="process-log-list">
                                @foreach ($processLogs as $log)
                                    <div class="compact-card">
                                        <div class="compact-summary">
                                            <div class="compact-title">
                                                <strong>{{ $log->message }}</strong>
                                                <div class="meta">{{ $log->kind }} · {{ $log->status }} · {{ $log->created_at?->diffForHumans() }}</div>
                                                <div class="pill-row">
                                                    @if ($log->agent)
                                                        <span class="pill">{{ $log->agent->name }}</span>
                                                    @endif
                                                    @if ($log->agentRuntime)
                                                        <span class="pill">{{ $log->agentRuntime->name }}</span>
                                                    @endif
                                                    @if ($log->task)
                                                        <span class="pill">{{ $log->task->name }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <div class="details-grid" style="margin-top: 12px;">
                            <div class="meta">External sessions</div>
                            <div class="cards" id="external-session-list">
                                @forelse ($externalSessions as $session)
                                    <div class="compact-card">
                                        <div class="compact-summary">
                                            <div class="compact-title">
                                                <strong>{{ $session->title ?? $session->external_id }}</strong>
                                                <div class="meta">{{ $session->harness }} · {{ $session->status }} · {{ $session->last_seen_at?->diffForHumans() }}</div>
                                                <div class="pill-row">
                                                    <span class="pill active">{{ $session->external_id }}</span>
                                                    @if ($session->agentRuntime)
                                                        <span class="pill">{{ $session->agentRuntime->name }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty">No external runtime sessions recorded yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h3>Tracked Tasks</h3>
                            <p>Compact task cards keep the queue readable while exposing edit and delete controls on demand.</p>
                        </div>
                        <div class="section-actions">
                            <details class="inline-toggle">
                                <summary class="toggle-summary tiny">New Task</summary>
                                <div class="panel-body">
                                    <form class="form-grid two" method="POST" action="/coordinator/tasks">
                                        @csrf
                                        <select name="project_id" required>
                                            <option value="">Choose project</option>
                                            @foreach ($projects as $project)
                                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                                            @endforeach
                                        </select>
                                        <select name="workflow_id" required>
                                            <option value="">Choose workflow</option>
                                            @foreach ($workflows as $workflow)
                                                <option value="{{ $workflow->id }}">{{ $workflow->name }}</option>
                                            @endforeach
                                        </select>
                                        <select name="recommended_agent_id">
                                            <option value="">Suggested agent</option>
                                            @foreach ($assignableAgents as $assignableAgent)
                                                <option value="{{ $assignableAgent->id }}">{{ $assignableAgent->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="name" placeholder="Task name" required>
                                        <textarea name="description" placeholder="Task description"></textarea>
                                        <button type="submit">Add Task</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </div>
                    <div class="panel-body">
                        @if ($tasks->isEmpty())
                            <div class="empty">No tasks have been captured yet.</div>
                        @else
                            <div class="cards">
                                @foreach ($tasks as $task)
                                    <details class="compact-card">
                                        <summary class="compact-summary">
                                            <div class="compact-title">
                                                <strong>{{ $task->name }}</strong>
                                                <div class="meta">{{ $task->project?->name ?? 'Unknown project' }} · {{ $task->workflow?->name ?? 'Unknown workflow' }}</div>
                                                <div class="pill-row">
                                                    <span class="pill {{ $task->status === 'in-progress' ? 'active' : '' }}">{{ $task->status }}</span>
                                                    @if ($task->recommendedAgent)
                                                        <span class="pill active">{{ $task->recommendedAgent->name }}</span>
                                                    @endif
                                                    <span class="pill">{{ $task->subtasks->count() }} subtasks</span>
                                                    @if ($task->currentSubtask)
                                                        <span class="pill">{{ $task->currentSubtask->name }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="button-link secondary tiny">Details</span>
                                        </summary>
                                        <div class="card-body">
                                            @if ($task->description)
                                                <div class="meta">{{ $task->description }}</div>
                                            @endif
                                            @if ($task->result && in_array($task->status, ['completed', 'failed']))
                                                <div class="meta" style="font-style:italic; margin-top:4px;">{{ Str::limit($task->result, 160) }}</div>
                                            @endif
                                            <form class="details-grid" method="POST" action="/coordinator/tasks/{{ $task->id }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="text" name="name" value="{{ $task->name }}" required>
                                                <textarea name="description" placeholder="Task description">{{ $task->description }}</textarea>
                                                <div class="card-actions">
                                                    <select name="recommended_agent_id">
                                                        <option value="">Suggested agent</option>
                                                        @foreach ($assignableAgents as $assignableAgent)
                                                            <option value="{{ $assignableAgent->id }}" @selected($task->recommended_agent_id === $assignableAgent->id)>{{ $assignableAgent->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="status" required>
                                                        @foreach (['pending', 'in-progress', 'completed', 'failed'] as $status)
                                                            <option value="{{ $status }}" @selected($task->status === $status)>{{ $status }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button class="tiny" type="submit">Save</button>
                                                </div>
                                            </form>
                                            <form method="POST" action="/coordinator/tasks/{{ $task->id }}" onsubmit="return confirm('Delete this task?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="danger tiny" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h3>Recent Heartbeats</h3>
                            <p>Latest provider sync and queueing decisions.</p>
                        </div>
                    </div>
                    <div class="panel-body">
                        @if ($heartbeatLogs->isEmpty())
                            <div class="empty">No heartbeat runs recorded yet.</div>
                        @else
                            <div class="cards">
                                @foreach ($heartbeatLogs as $heartbeatLog)
                                    <div class="compact-card">
                                        <div class="compact-summary">
                                            <div class="compact-title">
                                                <strong>{{ optional($heartbeatLog->timestamp)->toDayDateTimeString() }}</strong>
                                                <div class="meta">Queued tasks: {{ count($heartbeatLog->tasks_queued ?? []) }} · Provider snapshots: {{ count($heartbeatLog->provider_status ?? []) }}</div>
                                                @if (! empty($heartbeatLog->decisions))
                                                    <div class="pill-row">
                                                        @foreach (collect($heartbeatLog->decisions)->take(4) as $decision)
                                                            <span class="pill">{{ $decision['type'] ?? 'decision' }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h3>Projects & Workflows</h3>
                            <p>Phase 0 objects stay visible, while creation forms stay tucked away until needed.</p>
                        </div>
                        <div class="toolbar">
                            <details class="inline-toggle">
                                <summary class="toggle-summary tiny">New Project</summary>
                                <div class="panel-body">
                                    <form class="form-grid" method="POST" action="/coordinator/projects">
                                        @csrf
                                        <input type="text" name="name" placeholder="Project name" required>
                                        <textarea name="description" placeholder="Project description"></textarea>
                                        <textarea name="goals" placeholder="Goals"></textarea>
                                        <button type="submit">Add Project</button>
                                    </form>
                                </div>
                            </details>
                            <details class="inline-toggle">
                                <summary class="toggle-summary tiny">New Workflow</summary>
                                <div class="panel-body">
                                    <form class="form-grid" method="POST" action="/coordinator/workflows">
                                        @csrf
                                        <input type="text" name="name" placeholder="Workflow name" required>
                                        <textarea name="description" placeholder="Workflow description"></textarea>
                                        <button type="submit">Add Workflow</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="catalog-grid">
                            @foreach ($projects as $project)
                                <article class="catalog-card">
                                    <h4>{{ $project->name }}</h4>
                                    <div class="meta">{{ $project->status }} · {{ $project->tasks->count() }} tasks</div>
                                    @if ($project->current_intent)
                                        <div class="meta">{{ $project->current_intent }}</div>
                                    @endif
                                </article>
                            @endforeach
                            @foreach ($workflows as $workflow)
                                <article class="catalog-card">
                                    <h4>{{ $workflow->name }}</h4>
                                    <div class="meta">{{ count($workflow->steps ?? []) }} steps</div>
                                    <div class="pill-row">
                                        @foreach (($workflow->steps ?? []) as $step)
                                            <span class="pill">{{ $step['name'] ?? 'Step' }}</span>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>

            <div class="stack">
                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h3>Agents</h3>
                            <p>Each logical agent can expose multiple runtimes, so coordinator routing can shift across Laravel AI, OpenCode, and Codex later.</p>
                        </div>
                        <details class="inline-toggle">
                            <summary class="toggle-summary tiny">New Agent</summary>
                            <div class="panel-body">
                                <form class="form-grid" method="POST" action="/coordinator/agents">
                                    @csrf
                                    <input type="text" name="name" placeholder="Agent name" required>
                                    <input type="text" name="role" placeholder="Role" required>
                                    <textarea name="description" placeholder="Description"></textarea>
                                    <input type="text" name="tools_text" placeholder="Tools, comma separated">
                                    <button type="submit">Add Agent</button>
                                </form>
                            </div>
                        </details>
                        <details class="inline-toggle">
                            <summary class="toggle-summary tiny">New Runtime</summary>
                            <div class="panel-body">
                                <form class="form-grid" method="POST" action="/coordinator/agent-runtimes">
                                    @csrf
                                    <select name="agent_id" required>
                                        <option value="">Choose agent</option>
                                        @foreach ($agents as $agent)
                                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="name" placeholder="Runtime name" required>
                                    <select name="harness" required>
                                        @foreach (['laravel_ai', 'opencode', 'claude_code', 'codex'] as $harness)
                                            <option value="{{ $harness }}">{{ $harness }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="runtime_type" placeholder="Runtime type" value="laravel_class" required>
                                    <input type="text" name="runtime_ref" placeholder="Runtime ref / agent id" required>
                                    <select name="provider_route_id">
                                        <option value="">Primary route</option>
                                        @foreach ($providerRoutes as $route)
                                            <option value="{{ $route->id }}">{{ $route->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="provider_model_id">
                                        <option value="">Primary model</option>
                                        @foreach ($providerModels as $providerModel)
                                            <option value="{{ $providerModel->id }}">{{ $providerModel->route->name }} · {{ $providerModel->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="fallback_provider_route_id">
                                        <option value="">Fallback route</option>
                                        @foreach ($providerRoutes as $route)
                                            <option value="{{ $route->id }}">{{ $route->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="fallback_provider_model_id">
                                        <option value="">Fallback model</option>
                                        @foreach ($providerModels as $providerModel)
                                            <option value="{{ $providerModel->id }}">{{ $providerModel->route->name }} · {{ $providerModel->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="tools_text" placeholder="Tools, comma separated">
                                    <textarea name="config_text" placeholder='Runtime config JSON'></textarea>
                                    <label class="meta"><input type="checkbox" name="is_default" value="1"> Default for harness</label>
                                    <select name="status" required>
                                        @foreach (['active', 'disabled'] as $status)
                                            <option value="{{ $status }}">{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit">Add Runtime</button>
                                </form>
                            </div>
                        </details>
                    </div>
                    <div class="panel-body">
                        <div class="cards">
                            @foreach ($agents as $agent)
                                <details class="compact-card">
                                    <summary class="compact-summary">
                                        <div class="compact-title">
                                            <strong>{{ $agent->name }}</strong>
                                            <div class="meta">{{ $agent->role }}</div>
                                            <div class="pill-row">
                                                <span class="pill">{{ $agent->runtimes->count() }} runtimes</span>
                                            </div>
                                        </div>
                                        <span class="button-link secondary tiny">Details</span>
                                    </summary>
                                    <div class="card-body">
                                        <form class="details-grid" method="POST" action="/coordinator/agents/{{ $agent->id }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="name" value="{{ $agent->name }}" required>
                                            <input type="text" name="role" value="{{ $agent->role }}" required>
                                            <textarea name="description" placeholder="Description">{{ $agent->description }}</textarea>
                                            <input type="text" name="tools_text" value="{{ implode(', ', $agent->tools ?? []) }}" placeholder="Tools, comma separated">
                                            <button class="tiny" type="submit">Save Agent</button>
                                        </form>
                                        @if ($agent->runtimes->isNotEmpty())
                                            <div class="details-grid">
                                                <div class="meta">Configured runtimes</div>
                                                @foreach ($agent->runtimes->sortByDesc('is_default') as $runtime)
                                                    <details class="compact-card">
                                                        <summary class="compact-summary">
                                                            <div class="compact-title">
                                                                <strong>{{ $runtime->name }}</strong>
                                                                <div class="meta">{{ $runtime->harness }} · {{ $runtime->runtime_type }} · {{ $runtime->runtime_ref }}</div>
                                                                <div class="pill-row">
                                                                    @if ($runtime->is_default)
                                                                        <span class="pill active">default</span>
                                                                    @endif
                                                                    <span class="pill">{{ $runtime->route?->name ?? 'no route' }}</span>
                                                                    @if ($runtime->model)
                                                                        <span class="pill">{{ $runtime->model->name }}</span>
                                                                    @endif
                                                                    @if ($runtime->fallbackRoute)
                                                                        <span class="pill">fallback {{ $runtime->fallbackRoute->name }}</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </summary>
                                                        <div class="card-body">
                                                            <form class="details-grid" method="POST" action="/coordinator/agent-runtimes/{{ $runtime->id }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="text" name="name" value="{{ $runtime->name }}" required>
                                                                <select name="harness" required>
                                                                    @foreach (['laravel_ai', 'opencode', 'claude_code', 'codex'] as $harness)
                                                                        <option value="{{ $harness }}" @selected($runtime->harness === $harness)>{{ $harness }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <input type="text" name="runtime_type" value="{{ $runtime->runtime_type }}" required>
                                                                <input type="text" name="runtime_ref" value="{{ $runtime->runtime_ref }}" required>
                                                                <textarea name="description" placeholder="Description">{{ $runtime->description }}</textarea>
                                                                <select name="provider_route_id">
                                                                    <option value="">Primary route</option>
                                                                    @foreach ($providerRoutes as $route)
                                                                        <option value="{{ $route->id }}" @selected($runtime->provider_route_id === $route->id)>{{ $route->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <select name="provider_model_id">
                                                                    <option value="">Primary model</option>
                                                                    @foreach ($providerModels as $providerModel)
                                                                        <option value="{{ $providerModel->id }}" @selected($runtime->provider_model_id === $providerModel->id)>{{ $providerModel->route->name }} · {{ $providerModel->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <select name="fallback_provider_route_id">
                                                                    <option value="">Fallback route</option>
                                                                    @foreach ($providerRoutes as $route)
                                                                        <option value="{{ $route->id }}" @selected($runtime->fallback_provider_route_id === $route->id)>{{ $route->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <select name="fallback_provider_model_id">
                                                                    <option value="">Fallback model</option>
                                                                    @foreach ($providerModels as $providerModel)
                                                                        <option value="{{ $providerModel->id }}" @selected($runtime->fallback_provider_model_id === $providerModel->id)>{{ $providerModel->route->name }} · {{ $providerModel->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <input type="text" name="tools_text" value="{{ implode(', ', $runtime->tools ?? []) }}" placeholder="Tools, comma separated">
                                                                <textarea name="config_text" placeholder="Runtime config JSON">{{ json_encode($runtime->config ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                                                                <label class="meta"><input type="checkbox" name="is_default" value="1" @checked($runtime->is_default)> Default for harness</label>
                                                                <select name="status" required>
                                                                    @foreach (['active', 'disabled'] as $status)
                                                                        <option value="{{ $status }}" @selected($runtime->status === $status)>{{ $status }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <button class="tiny" type="submit">Save Runtime</button>
                                                            </form>
                                                            <form method="POST" action="/coordinator/agent-runtimes/{{ $runtime->id }}" onsubmit="return confirm('Delete this runtime?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="danger tiny" type="submit">Delete Runtime</button>
                                                            </form>
                                                        </div>
                                                    </details>
                                                @endforeach
                                            </div>
                                        @endif
                                        <form method="POST" action="/coordinator/agents/{{ $agent->id }}" onsubmit="return confirm('Delete this agent?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="danger tiny" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h3>Providers</h3>
                            <p>Providers now hold logical upstreams, while route and model details show how each harness can actually reach them.</p>
                        </div>
                        <details class="inline-toggle">
                            <summary class="toggle-summary tiny">New Provider</summary>
                            <div class="panel-body">
                                <form class="form-grid" method="POST" action="/coordinator/providers">
                                    @csrf
                                    <input type="text" name="name" placeholder="Provider name" required>
                                    <select name="type" required>
                                        <option value="subscription">subscription</option>
                                        <option value="hybrid">hybrid</option>
                                        <option value="api-only">api-only</option>
                                    </select>
                                    <select name="api_protocol" required>
                                        <option value="Anthropic-compatible">Anthropic-compatible</option>
                                        <option value="native">native</option>
                                        <option value="OpenAI-compatible">OpenAI-compatible</option>
                                    </select>
                                    <select name="status" required>
                                        @foreach (['active', 'degraded', 'rate-limited', 'disabled'] as $status)
                                            <option value="{{ $status }}">{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="capability_tags_text" placeholder="Capabilities, comma separated">
                                    <textarea name="priority_preferences_text" placeholder='Priority JSON, eg {"chat": 1, "planning": 2}'></textarea>
                                    <textarea name="rate_limits_text" placeholder='Rate limit JSON, eg {"requests_per_window": 100}'></textarea>
                                    <textarea name="usage_snapshot_text" placeholder='Usage JSON, eg {"requests_remaining": 50}'></textarea>
                                    <button type="submit">Add Provider</button>
                                </form>
                            </div>
                        </details>
                        <details class="inline-toggle">
                            <summary class="toggle-summary tiny">New Route</summary>
                            <div class="panel-body">
                                <form class="form-grid" method="POST" action="/coordinator/provider-routes">
                                    @csrf
                                    <select name="provider_id" required>
                                        <option value="">Choose provider</option>
                                        @foreach ($providers as $provider)
                                            <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="name" placeholder="Route name" required>
                                    <select name="harness" required>
                                        @foreach (['laravel_ai', 'opencode', 'claude_code', 'codex'] as $harness)
                                            <option value="{{ $harness }}">{{ $harness }}</option>
                                        @endforeach
                                    </select>
                                    <select name="auth_mode" required>
                                        @foreach (['api_key', 'chatgpt_oauth', 'provider_oauth'] as $authMode)
                                            <option value="{{ $authMode }}">{{ $authMode }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="credential_type" placeholder="Credential type">
                                    <input type="number" name="priority" placeholder="Priority" value="100">
                                    <input type="text" name="capability_tags_text" placeholder="Capabilities, comma separated">
                                    <textarea name="rate_limits_text" placeholder='Rate limit JSON'></textarea>
                                    <textarea name="usage_snapshot_text" placeholder='Usage JSON'></textarea>
                                    <textarea name="config_text" placeholder='Route config JSON'></textarea>
                                    <label class="meta"><input type="checkbox" name="supports_tools" value="1"> Supports tools</label>
                                    <label class="meta"><input type="checkbox" name="supports_structured_output" value="1"> Supports structured output</label>
                                    <select name="status" required>
                                        @foreach (['active', 'degraded', 'rate-limited', 'disabled'] as $status)
                                            <option value="{{ $status }}">{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit">Add Route</button>
                                </form>
                            </div>
                        </details>
                        <details class="inline-toggle">
                            <summary class="toggle-summary tiny">New Model</summary>
                            <div class="panel-body">
                                <form class="form-grid" method="POST" action="/coordinator/provider-models">
                                    @csrf
                                    <select name="provider_route_id" required>
                                        <option value="">Choose route</option>
                                        @foreach ($providerRoutes as $route)
                                            <option value="{{ $route->id }}">{{ $route->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="name" placeholder="Model name" required>
                                    <input type="text" name="external_name" placeholder="External/provider model name">
                                    <input type="text" name="capabilities_text" placeholder="Capabilities, comma separated">
                                    <input type="number" name="context_window" placeholder="Context window">
                                    <input type="number" name="priority" placeholder="Priority" value="100">
                                    <textarea name="config_text" placeholder='Model config JSON'></textarea>
                                    <label class="meta"><input type="checkbox" name="is_default" value="1"> Default model</label>
                                    <select name="status" required>
                                        @foreach (['active', 'disabled'] as $status)
                                            <option value="{{ $status }}">{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit">Add Model</button>
                                </form>
                            </div>
                        </details>
                    </div>
                    <div class="panel-body">
                        <div class="cards">
                            @foreach ($providers as $provider)
                                <details class="compact-card">
                                    <summary class="compact-summary">
                                        <div class="compact-title">
                                            <strong>{{ $provider->name }}</strong>
                                            <div class="meta">{{ $provider->type }} · {{ $provider->api_protocol }}</div>
                                            <div class="pill-row">
                                                <span class="pill {{ $provider->status === 'active' ? 'active' : ($provider->status === 'rate-limited' ? 'rate-limited' : '') }}">{{ $provider->status }}</span>
                                                <span class="pill">Remaining {{ $provider->usage_snapshot['requests_remaining'] ?? 'n/a' }}</span>
                                                @foreach (($provider->capability_tags ?? []) as $capability)
                                                    <span class="pill">{{ $capability }}</span>
                                                @endforeach
                                                <span class="pill">{{ $provider->routes->count() }} routes</span>
                                            </div>
                                        </div>
                                        <span class="button-link secondary tiny">Details</span>
                                    </summary>
                                    <div class="card-body">
                                        <form class="details-grid" method="POST" action="/coordinator/providers/{{ $provider->id }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="name" value="{{ $provider->name }}" required>
                                            <select name="type" required>
                                                @foreach (['subscription', 'hybrid', 'api-only', 'API-key-based', 'CLI-tool-based'] as $type)
                                                    <option value="{{ $type }}" @selected($provider->type === $type)>{{ $type }}</option>
                                                @endforeach
                                            </select>
                                            <select name="api_protocol" required>
                                                @foreach (['Anthropic-compatible', 'native', 'OpenAI-compatible'] as $protocol)
                                                    <option value="{{ $protocol }}" @selected($provider->api_protocol === $protocol)>{{ $protocol }}</option>
                                                @endforeach
                                            </select>
                                            <select name="status" required>
                                                @foreach (['active', 'degraded', 'rate-limited', 'disabled'] as $status)
                                                    <option value="{{ $status }}" @selected($provider->status === $status)>{{ $status }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" name="capability_tags_text" value="{{ implode(', ', $provider->capability_tags ?? []) }}" placeholder="Capabilities, comma separated">
                                            <textarea name="priority_preferences_text" placeholder="Priority JSON">{{ json_encode($provider->priority_preferences ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                                            <textarea name="rate_limits_text" placeholder="Rate limit JSON">{{ json_encode($provider->rate_limits ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                                            <textarea name="usage_snapshot_text" placeholder="Usage JSON">{{ json_encode($provider->usage_snapshot ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                                            <button class="tiny" type="submit">Save Provider</button>
                                        </form>
                                        @if ($provider->routes->isNotEmpty())
                                            <div class="details-grid">
                                                <div class="meta">Configured routes and models</div>
                                                @foreach ($provider->routes->sortBy('priority') as $route)
                                                    <details class="compact-card">
                                                        <summary class="compact-summary">
                                                            <div class="compact-title">
                                                                <strong>{{ $route->name }}</strong>
                                                                <div class="meta">{{ $route->harness }} · {{ $route->auth_mode }} · priority {{ $route->priority }}</div>
                                                                <div class="pill-row">
                                                                    <span class="pill {{ $route->status === 'active' ? 'active' : ($route->status === 'rate-limited' ? 'rate-limited' : '') }}">{{ $route->status }}</span>
                                                                    <span class="pill">{{ count($route->models) }} models</span>
                                                                    @foreach (($route->capability_tags ?? []) as $capability)
                                                                        <span class="pill">{{ $capability }}</span>
                                                                    @endforeach
                                                                </div>
                                                                @if ($route->models->isNotEmpty())
                                                                    <div class="pill-row">
                                                                    @foreach ($route->models->sortBy('priority') as $model)
                                                                        <span class="pill {{ $model->is_default ? 'active' : '' }}">{{ $model->name }}</span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                        </summary>
                                                        <div class="card-body">
                                                            <form class="details-grid" method="POST" action="/coordinator/provider-routes/{{ $route->id }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="text" name="name" value="{{ $route->name }}" required>
                                                                <select name="harness" required>
                                                                    @foreach (['laravel_ai', 'opencode', 'claude_code', 'codex'] as $harness)
                                                                        <option value="{{ $harness }}" @selected($route->harness === $harness)>{{ $harness }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <select name="auth_mode" required>
                                                                    @foreach (['api_key', 'chatgpt_oauth', 'provider_oauth'] as $authMode)
                                                                        <option value="{{ $authMode }}" @selected($route->auth_mode === $authMode)>{{ $authMode }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <input type="text" name="credential_type" value="{{ $route->credential_type }}" placeholder="Credential type">
                                                                <input type="number" name="priority" value="{{ $route->priority }}" placeholder="Priority">
                                                                <input type="text" name="capability_tags_text" value="{{ implode(', ', $route->capability_tags ?? []) }}" placeholder="Capabilities, comma separated">
                                                                <textarea name="rate_limits_text" placeholder="Rate limit JSON">{{ json_encode($route->rate_limits ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                                                                <textarea name="usage_snapshot_text" placeholder="Usage JSON">{{ json_encode($route->usage_snapshot ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                                                                <textarea name="config_text" placeholder="Route config JSON">{{ json_encode($route->config ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                                                                <label class="meta"><input type="checkbox" name="supports_tools" value="1" @checked($route->supports_tools)> Supports tools</label>
                                                                <label class="meta"><input type="checkbox" name="supports_structured_output" value="1" @checked($route->supports_structured_output)> Supports structured output</label>
                                                                <select name="status" required>
                                                                    @foreach (['active', 'degraded', 'rate-limited', 'disabled'] as $status)
                                                                        <option value="{{ $status }}" @selected($route->status === $status)>{{ $status }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <button class="tiny" type="submit">Save Route</button>
                                                            </form>
                                                            <div class="details-grid">
                                                                @foreach ($route->models->sortBy('priority') as $model)
                                                                    <details class="compact-card">
                                                                        <summary class="compact-summary">
                                                                            <div class="compact-title">
                                                                                <strong>{{ $model->name }}</strong>
                                                                                <div class="meta">{{ $model->external_name }} · priority {{ $model->priority }}</div>
                                                                                <div class="pill-row">
                                                                                    @if ($model->is_default)
                                                                                        <span class="pill active">default</span>
                                                                                    @endif
                                                                                    <span class="pill">{{ $model->status }}</span>
                                                                                    @foreach (($model->capabilities ?? []) as $capability)
                                                                                        <span class="pill">{{ $capability }}</span>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        </summary>
                                                                        <div class="card-body">
                                                                            <form class="details-grid" method="POST" action="/coordinator/provider-models/{{ $model->id }}">
                                                                                @csrf
                                                                                @method('PATCH')
                                                                                <input type="text" name="name" value="{{ $model->name }}" required>
                                                                                <input type="text" name="external_name" value="{{ $model->external_name }}" placeholder="External model name">
                                                                                <input type="text" name="capabilities_text" value="{{ implode(', ', $model->capabilities ?? []) }}" placeholder="Capabilities, comma separated">
                                                                                <input type="number" name="context_window" value="{{ $model->context_window }}" placeholder="Context window">
                                                                                <input type="number" name="priority" value="{{ $model->priority }}" placeholder="Priority">
                                                                                <textarea name="config_text" placeholder="Model config JSON">{{ json_encode($model->config ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                                                                                <label class="meta"><input type="checkbox" name="is_default" value="1" @checked($model->is_default)> Default model</label>
                                                                                <select name="status" required>
                                                                                    @foreach (['active', 'disabled'] as $status)
                                                                                        <option value="{{ $status }}" @selected($model->status === $status)>{{ $status }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                                <button class="tiny" type="submit">Save Model</button>
                                                                            </form>
                                                                            <form method="POST" action="/coordinator/provider-models/{{ $model->id }}" onsubmit="return confirm('Delete this model?');">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <button class="danger tiny" type="submit">Delete Model</button>
                                                                            </form>
                                                                        </div>
                                                                    </details>
                                                                @endforeach
                                                            </div>
                                                            <form method="POST" action="/coordinator/provider-routes/{{ $route->id }}" onsubmit="return confirm('Delete this route?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="danger tiny" type="submit">Delete Route</button>
                                                            </form>
                                                        </div>
                                                    </details>
                                                @endforeach
                                            </div>
                                        @endif
                                        <form method="POST" action="/coordinator/providers/{{ $provider->id }}" onsubmit="return confirm('Delete this provider?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="danger tiny" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chatMessages = document.getElementById('chat-messages');
            const userInput = document.getElementById('user-input');
            const sendButton = document.getElementById('send-button');
            const statusElement = document.getElementById('status');

            function renderPendingDraft(draft) {
                const panel = document.getElementById('pending-draft-panel');

                if (! panel) {
                    return;
                }

                if (! draft) {
                    panel.innerHTML = '<div class="empty" id="pending-draft-empty">No draft in progress. Ask Clawra about a task to begin refining one.</div>';
                    return;
                }

                const questions = Array.isArray(draft.clarifying_questions) && draft.clarifying_questions.length > 0
                    ? `<div class="details-grid"><div class="meta">Clarifying questions</div>${draft.clarifying_questions.map((question) => `<div class="meta">- ${question}</div>`).join('')}</div>`
                    : '';

                panel.innerHTML = `
                    <div class="compact-card" id="pending-draft-card">
                        <div class="compact-summary">
                            <div class="compact-title">
                                <strong id="pending-draft-title">${draft.title ?? 'Untitled draft'}</strong>
                                <div class="meta" id="pending-draft-summary">${draft.summary ?? ''}</div>
                                <div class="pill-row">
                                    <span class="pill">${draft.workflow_type ?? 'general'}</span>
                                    ${draft.recommended_agent ? `<span class="pill active" id="pending-draft-agent">${draft.recommended_agent}</span>` : ''}
                                    ${draft.needs_clarification ? '<span class="pill rate-limited">needs clarification</span>' : '<span class="pill active">ready to confirm</span>'}
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            ${draft.description ? `<div class="meta">${draft.description}</div>` : ''}
                            ${questions}
                            <div class="meta">Reply in chat with  60confirm 60 to create this task, or describe what to revise.</div>
                        </div>
                    </div>`;
            }

            function renderActivity(processLogs = [], externalSessions = []) {
                const processLogList = document.getElementById('process-log-list');
                const externalSessionList = document.getElementById('external-session-list');

                if (processLogList && Array.isArray(processLogs) && processLogs.length > 0) {
                    processLogList.innerHTML = processLogs.map((log) => `
                        <div class="compact-card">
                            <div class="compact-summary">
                                <div class="compact-title">
                                    <strong>${log.message ?? 'Activity'}</strong>
                                    <div class="meta">${log.kind ?? 'event'} · ${log.status ?? 'info'} · ${log.created_at ?? ''}</div>
                                </div>
                            </div>
                        </div>`).join('');
                }

                if (externalSessionList && Array.isArray(externalSessions)) {
                    if (externalSessions.length === 0) {
                        externalSessionList.innerHTML = '<div class="empty">No external runtime sessions recorded yet.</div>';
                    } else {
                        externalSessionList.innerHTML = externalSessions.map((session) => `
                            <div class="compact-card">
                                <div class="compact-summary">
                                    <div class="compact-title">
                                        <strong>${session.title ?? session.external_id ?? 'External session'}</strong>
                                        <div class="meta">${session.harness ?? 'runtime'} · ${session.status ?? 'active'} · ${session.last_seen_at ?? ''}</div>
                                        <div class="pill-row">
                                            <span class="pill active">${session.external_id ?? 'unknown'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>`).join('');
                    }
                }
            }

            function addMessage(role, content, meta = {}) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${role}-message`;

                const headerDiv = document.createElement('div');
                headerDiv.className = 'message-header';
                headerDiv.textContent = role === 'user' ? 'You' : 'Coordinator';

                const contentDiv = document.createElement('div');
                contentDiv.className = 'message-content';
                contentDiv.textContent = content;

                messageDiv.appendChild(headerDiv);
                messageDiv.appendChild(contentDiv);

                if (role === 'assistant' && meta.created_task && meta.task_type && meta.project) {
                    const badgeDiv = document.createElement('div');
                    badgeDiv.className = 'message-badge';
                    badgeDiv.textContent = `${meta.task_type} -> ${meta.project}`;
                    messageDiv.appendChild(badgeDiv);
                }

                if (role === 'assistant') {
                    if (meta.pending_task_draft) {
                        renderPendingDraft(meta.pending_task_draft);
                    } else if (meta.created_task) {
                        renderPendingDraft(null);
                    }

                    if (meta.process_logs || meta.external_sessions) {
                        renderActivity(meta.process_logs || [], meta.external_sessions || []);
                    }
                }

                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            async function sendMessage() {
                const message = userInput.value.trim();

                if (! message) {
                    return;
                }

                addMessage('user', message);
                userInput.value = '';
                statusElement.textContent = 'Processing';

                try {
                    const projectId = document.getElementById('active-project-id')?.value;
                    const conversationId = document.getElementById('active-conversation-id')?.value;

                    const response = await fetch('/coordinator/message', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        body: JSON.stringify({ message, project_id: projectId, conversation_id: conversationId }),
                    });

                    if (! response.ok) {
                        throw new Error(`HTTP error ${response.status}`);
                    }

                    const data = await response.json();
                    addMessage('assistant', data.response, data.meta || {});
                    statusElement.textContent = 'Ready';
                } catch (error) {
                    addMessage('assistant', 'Sorry, I hit an error while orchestrating that request.');
                    statusElement.textContent = 'Error';
                    console.error(error);
                }
            }

            sendButton.addEventListener('click', sendMessage);
            userInput.addEventListener('keypress', function (event) {
                if (event.key === 'Enter') {
                    sendMessage();
                }
            });
        });
    </script>
</body>
</html>
