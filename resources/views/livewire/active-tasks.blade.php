<div wire:poll.5s>
    @php
        $kindLabels = [
            'runtime.execution.started'   => 'Started',
            'runtime.execution.completed' => 'Agent response',
            'runtime.execution.failed'    => 'Runtime failed',
            'runtime.execution.fallback'  => 'Fallback triggered',
            'task.execution.completed'    => 'Task completed',
            'task.execution.failed'       => 'Task failed',
            'runtime.execution.missing'   => 'No runtime',
        ];
    @endphp

    @if ($selectedTask)
        {{-- Two-panel layout --}}
        <div style="display:flex; gap:0; min-height:300px;">

            {{-- Left: Task list (~40%) --}}
            <div style="width:40%; border-right:1px solid var(--border); flex-shrink:0;">
                <div class="panel-header" style="border-bottom:1px solid var(--border);">
                    <h2>Active Tasks</h2>
                    <div class="panel-header-actions">
                        <span class="meta-item">{{ $tasks->count() }} tasks</span>
                        @if ($projects->isNotEmpty() && $workflows->isNotEmpty())
                            <button class="btn btn-sm btn-primary" wire:click="toggleCreateForm">+ Add Task</button>
                        @endif
                    </div>
                </div>

                @if ($showCreateForm && $projects->isNotEmpty() && $workflows->isNotEmpty())
                    <div class="inline-form open" style="border-bottom:1px solid var(--border);">
                        <form method="POST" action="/admin/tasks">
                            @csrf
                            <div class="form-grid">
                                <div class="form-grid-2">
                                    <div class="form-row">
                                        <label>Project *</label>
                                        <select name="project_id" class="form-select" required>
                                            @foreach ($projects as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-row">
                                        <label>Workflow *</label>
                                        <select name="workflow_id" class="form-select" required>
                                            @foreach ($workflows as $w)
                                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-grid-2">
                                    <div class="form-row">
                                        <label>Task Name *</label>
                                        <input name="name" type="text" class="form-input" placeholder="e.g. Research competitors" required>
                                    </div>
                                    <div class="form-row">
                                        <label>Priority</label>
                                        <select name="priority" class="form-select">
                                            <option value="10">High (0–24)</option>
                                            <option value="50" selected>Normal (25–74)</option>
                                            <option value="80">Low (75–100)</option>
                                        </select>
                                    </div>
                                </div>
                                @if ($agents->isNotEmpty())
                                    <div class="form-row">
                                        <label>Recommended Agent</label>
                                        <select name="recommended_agent_id" class="form-select">
                                            <option value="">— None —</option>
                                            @foreach ($agents as $a)
                                                <option value="{{ $a->id }}">{{ $a->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="form-row">
                                    <label>Description</label>
                                    <textarea name="description" class="form-textarea" placeholder="What needs to happen?"></textarea>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-sm">Create Task</button>
                                    <button type="button" class="btn btn-ghost btn-sm" wire:click="toggleCreateForm">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif

                <div class="panel-body" style="padding-top:8px; padding-bottom:8px;">
                    @forelse ($tasks as $task)
                        <div class="task-row {{ $selectedTask && $selectedTask->id === $task->id ? 'task-row-selected' : '' }}"
                             wire:key="task-{{ $task->id }}">
                            <div class="task-main" wire:click="selectTask({{ $task->id }})" style="cursor:pointer;">
                                <span class="task-name">{{ $task->name }}</span>
                                <div class="task-meta">
                                    @if ($task->project)
                                        <span class="task-meta-item" style="color:var(--accent); font-weight:600;">{{ $task->project->name }}</span>
                                    @endif
                                    @php
                                        $priority = $task->priority ?? 50;
                                        [$priorityLabel, $priorityClass] = match(true) {
                                            $priority < 25 => ['High', 'priority-high'],
                                            $priority < 75 => ['', ''],
                                            default        => ['Low', 'priority-low'],
                                        };
                                    @endphp
                                    @if ($priorityLabel)
                                        <span class="task-meta-sep">·</span>
                                        <span class="{{ $priorityClass }}">{{ $priorityLabel }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="task-actions" wire:click.stop>
                                <form method="POST" action="/admin/tasks/{{ $task->id }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="name" value="{{ $task->name }}">
                                    <input type="hidden" name="description" value="{{ $task->description }}">
                                    <input type="hidden" name="priority" value="{{ $task->priority ?? 50 }}">
                                    <select name="status" class="form-select-sm" onchange="this.form.submit()" title="Change status">
                                        @foreach (['pending', 'in-progress', 'completed', 'failed'] as $s)
                                            <option value="{{ $s }}" @selected($task->status === $s)>{{ ucwords(str_replace('-', ' ', $s)) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                <form method="POST" action="/admin/tasks/{{ $task->id }}" onsubmit="return confirm('Delete task?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-icon btn-danger" title="Delete task">×</button>
                                </form>
                            </div>
                        </div>
                        @if (!$loop->last)
                            <div class="panel-divider" style="margin: 0 14px;"></div>
                        @endif
                    @empty
                        <div class="empty">No active tasks.</div>
                    @endforelse
                </div>
            </div>

            {{-- Right: Detail panel (~60%) --}}
            <div style="width:60%; padding:16px 20px; overflow-y:auto; max-height:600px;">
                {{-- Task header --}}
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:12px;">
                    <div>
                        <div style="font-weight:700; font-size:1rem; margin-bottom:4px;">{{ $selectedTask->name }}</div>
                        <div class="task-meta" style="flex-wrap:wrap; gap:6px;">
                            @php
                                $statusColors = [
                                    'pending'     => ['var(--warning)', 'var(--warning-bg)'],
                                    'in-progress' => ['var(--accent)', 'var(--accent-soft)'],
                                    'completed'   => ['var(--success)', 'var(--success-bg)'],
                                    'failed'      => ['var(--danger)', 'var(--danger-bg)'],
                                    'draft'       => ['var(--muted)', 'var(--neutral-bg)'],
                                ];
                                [$sc, $sb] = $statusColors[$selectedTask->status] ?? ['var(--muted)', 'var(--neutral-bg)'];
                            @endphp
                            <span style="display:inline-block; padding:2px 8px; border-radius:99px; font-size:0.75rem; font-weight:600; color:{{ $sc }}; background:{{ $sb }};">
                                {{ ucwords(str_replace('-', ' ', $selectedTask->status)) }}
                            </span>
                            @if ($selectedTask->project)
                                <span class="task-meta-sep">·</span>
                                <span class="task-meta-item" style="color:var(--accent); font-weight:600;">{{ $selectedTask->project->name }}</span>
                            @endif
                            @if ($selectedTask->workflow)
                                <span class="task-meta-sep">·</span>
                                <span class="task-meta-item">{{ $selectedTask->workflow->name }}</span>
                            @endif
                            @if ($selectedTask->recommendedAgent)
                                <span class="task-meta-sep">·</span>
                                <span class="task-meta-item">{{ $selectedTask->recommendedAgent->name }}</span>
                            @endif
                            @php
                                $dp = $selectedTask->priority ?? 50;
                                [$dpl, $dpc] = match(true) {
                                    $dp < 25 => ['High priority', 'priority-high'],
                                    $dp < 75 => ['', ''],
                                    default  => ['Low priority', 'priority-low'],
                                };
                            @endphp
                            @if ($dpl)
                                <span class="task-meta-sep">·</span>
                                <span class="{{ $dpc }}">{{ $dpl }}</span>
                            @endif
                        </div>
                    </div>
                    <button class="btn btn-ghost btn-sm" wire:click="selectTask({{ $selectedTask->id }})" style="flex-shrink:0;">✕ Close</button>
                </div>

                {{-- Description --}}
                @if ($selectedTask->description)
                    <div style="background:var(--neutral-bg); border-radius:6px; padding:10px 12px; margin-bottom:12px; font-size:0.85rem; color:var(--muted);">
                        {{ $selectedTask->description }}
                    </div>
                @endif

                {{-- Result --}}
                @if ($selectedTask->result && in_array($selectedTask->status, ['completed', 'failed']))
                    <div style="margin-bottom:14px;">
                        <div style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--muted); margin-bottom:6px;">Result</div>
                        <pre style="background:var(--neutral-bg); border:1px solid var(--border); border-radius:6px; padding:10px 12px; font-size:0.8rem; white-space:pre-wrap; word-break:break-word; max-height:200px; overflow-y:auto; margin:0;">{{ $selectedTask->result }}</pre>
                    </div>
                @endif

                {{-- Process logs --}}
                @if ($taskLogs->isNotEmpty())
                    <div style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--muted); margin-bottom:8px;">Process Logs</div>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        @foreach ($taskLogs as $log)
                            @php
                                $label = $kindLabels[$log->kind] ?? $log->kind;
                                $ctx = $log->context ?? [];
                                $isSuccess = in_array($log->status, ['success', 'completed', 'ok']);
                                $isFailure = in_array($log->status, ['failed', 'error']);
                            @endphp
                            <div style="border:1px solid var(--border); border-radius:6px; overflow:hidden;" wire:key="log-{{ $log->id }}">
                                <div style="display:flex; align-items:center; gap:8px; padding:8px 12px; background:var(--panel);">
                                    <span style="width:8px; height:8px; border-radius:50%; flex-shrink:0; background:{{ $isFailure ? 'var(--danger)' : ($isSuccess ? 'var(--success)' : 'var(--warning)') }};"></span>
                                    <span style="font-weight:600; font-size:0.82rem;">{{ $label }}</span>
                                    @if ($log->message && $log->message !== $label)
                                        <span style="color:var(--muted); font-size:0.8rem; flex:1;">{{ $log->message }}</span>
                                    @endif
                                    <span style="color:var(--muted); font-size:0.75rem; flex-shrink:0;">{{ $log->created_at->format('H:i:s') }}</span>
                                </div>

                                {{-- Expanded content --}}
                                @if ($log->kind === 'runtime.execution.started' && isset($ctx['prompt']))
                                    <div style="padding:8px 12px; border-top:1px solid var(--border); background:#fafaf8; font-size:0.78rem; color:var(--muted);">
                                        <strong>Prompt:</strong> {{ \Illuminate\Support\Str::limit($ctx['prompt'], 300) }}
                                    </div>
                                @endif

                                @if (in_array($log->kind, ['runtime.execution.completed', 'task.execution.completed']) && isset($ctx['text']))
                                    <div style="padding:8px 12px; border-top:1px solid var(--border); background:#fafaf8;">
                                        <pre style="font-size:0.78rem; white-space:pre-wrap; word-break:break-word; max-height:300px; overflow-y:auto; margin:0; color:var(--text);">{{ $ctx['text'] }}</pre>
                                    </div>
                                @endif

                                @if (in_array($log->kind, ['runtime.execution.failed', 'task.execution.failed']) && isset($ctx['error']))
                                    <div style="padding:8px 12px; border-top:1px solid var(--border); background:var(--danger-bg); font-size:0.8rem; color:var(--danger);">
                                        {{ $ctx['error'] }}
                                    </div>
                                @endif

                                @if (isset($ctx['review']))
                                    @php
                                        $review = $ctx['review'];
                                        $decision = $review['decision'] ?? ($review['verdict'] ?? null);
                                        $reasoning = $review['reasoning'] ?? ($review['reason'] ?? null);
                                        $reviewColor = match(true) {
                                            str_contains(strtolower((string) $decision), 'approve') => 'var(--success)',
                                            str_contains(strtolower((string) $decision), 'reject')  => 'var(--danger)',
                                            default => 'var(--warning)',
                                        };
                                        $reviewBg = match(true) {
                                            str_contains(strtolower((string) $decision), 'approve') => 'var(--success-bg)',
                                            str_contains(strtolower((string) $decision), 'reject')  => 'var(--danger-bg)',
                                            default => 'var(--warning-bg)',
                                        };
                                    @endphp
                                    <div style="padding:8px 12px; border-top:1px solid var(--border); background:#fafaf8; display:flex; flex-direction:column; gap:4px;">
                                        @if ($decision)
                                            <span style="display:inline-block; padding:2px 8px; border-radius:99px; font-size:0.75rem; font-weight:600; color:{{ $reviewColor }}; background:{{ $reviewBg }}; width:fit-content;">
                                                {{ $decision }}
                                            </span>
                                        @endif
                                        @if ($reasoning)
                                            <span style="font-size:0.78rem; color:var(--muted);">{{ $reasoning }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif ($selectedTask)
                    <div class="empty">No process logs yet.</div>
                @endif
            </div>
        </div>

    @else
        {{-- Single panel layout (no task selected) --}}
        <div class="panel-header">
            <h2>Active Tasks</h2>
            <div class="panel-header-actions">
                <span class="meta-item">{{ $tasks->count() }} tasks</span>
                @if ($projects->isNotEmpty() && $workflows->isNotEmpty())
                    <button class="btn btn-sm btn-primary" wire:click="toggleCreateForm">+ Add Task</button>
                @endif
            </div>
        </div>

        @if ($showCreateForm && $projects->isNotEmpty() && $workflows->isNotEmpty())
            <div class="inline-form open">
                <form method="POST" action="/admin/tasks">
                    @csrf
                    <div class="form-grid">
                        <div class="form-grid-2">
                            <div class="form-row">
                                <label>Project *</label>
                                <select name="project_id" class="form-select" required>
                                    @foreach ($projects as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-row">
                                <label>Workflow *</label>
                                <select name="workflow_id" class="form-select" required>
                                    @foreach ($workflows as $w)
                                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-row">
                                <label>Task Name *</label>
                                <input name="name" type="text" class="form-input" placeholder="e.g. Research competitors" required>
                            </div>
                            <div class="form-row">
                                <label>Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="10">High (0–24)</option>
                                    <option value="50" selected>Normal (25–74)</option>
                                    <option value="80">Low (75–100)</option>
                                </select>
                            </div>
                        </div>
                        @if ($agents->isNotEmpty())
                            <div class="form-row">
                                <label>Recommended Agent</label>
                                <select name="recommended_agent_id" class="form-select">
                                    <option value="">— None —</option>
                                    @foreach ($agents as $a)
                                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="form-row">
                            <label>Description</label>
                            <textarea name="description" class="form-textarea" placeholder="What needs to happen?"></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-sm">Create Task</button>
                            <button type="button" class="btn btn-ghost btn-sm" wire:click="toggleCreateForm">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        @endif

        <div class="panel-body" style="padding-top:8px; padding-bottom:8px;">
            @forelse ($tasks as $task)
                <div class="task-row" wire:key="task-{{ $task->id }}">
                    <div class="task-main" wire:click="selectTask({{ $task->id }})" style="cursor:pointer;">
                        <span class="task-name">{{ $task->name }}</span>
                        @if ($task->result && in_array($task->status, ['completed', 'failed']))
                            <div class="task-result">{{ \Illuminate\Support\Str::limit($task->result, 160) }}</div>
                        @endif
                        <div class="task-meta">
                            @if ($task->project)
                                <a href="/coordinator?project_id={{ $task->project->id }}" class="task-meta-item" style="color:var(--accent); font-weight:600;">
                                    {{ $task->project->name }}
                                </a>
                            @endif
                            @if ($task->workflow)
                                <span class="task-meta-sep">·</span>
                                <span class="task-meta-item">{{ $task->workflow->name }}</span>
                            @endif
                            @if ($task->recommendedAgent)
                                <span class="task-meta-sep">·</span>
                                <span class="task-meta-item">{{ $task->recommendedAgent->name }}</span>
                            @endif
                            @php
                                $priority = $task->priority ?? 50;
                                [$priorityLabel, $priorityClass] = match(true) {
                                    $priority < 25 => ['High', 'priority-high'],
                                    $priority < 75 => ['', ''],
                                    default        => ['Low', 'priority-low'],
                                };
                            @endphp
                            @if ($priorityLabel)
                                <span class="task-meta-sep">·</span>
                                <span class="{{ $priorityClass }}">{{ $priorityLabel }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="task-actions" wire:click.stop>
                        <form method="POST" action="/admin/tasks/{{ $task->id }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="name" value="{{ $task->name }}">
                            <input type="hidden" name="description" value="{{ $task->description }}">
                            <input type="hidden" name="priority" value="{{ $task->priority ?? 50 }}">
                            <select name="status" class="form-select-sm" onchange="this.form.submit()" title="Change status">
                                @foreach (['pending', 'in-progress', 'completed', 'failed'] as $s)
                                    <option value="{{ $s }}" @selected($task->status === $s)>{{ ucwords(str_replace('-', ' ', $s)) }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="/admin/tasks/{{ $task->id }}" onsubmit="return confirm('Delete task?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-icon btn-danger" title="Delete task">×</button>
                        </form>
                    </div>
                </div>
                @if (!$loop->last)
                    <div class="panel-divider" style="margin: 0 14px;"></div>
                @endif
            @empty
                <div class="empty">No active tasks.</div>
            @endforelse
        </div>
    @endif

    <style>
        .task-row-selected {
            background: var(--accent-soft);
            border-radius: 4px;
        }
    </style>
</div>
