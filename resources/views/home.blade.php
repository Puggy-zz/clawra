<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Clawra</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

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
            --success-bg: #d6ede3;
            --warning: #9a6700;
            --warning-bg: #fef3cd;
            --danger: #8a3b2f;
            --danger-bg: #fae0db;
            --neutral-bg: #e8e4dd;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            font-size: 14px;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(52, 92, 105, 0.18), transparent 28%),
                radial-gradient(circle at top right, rgba(166, 123, 91, 0.16), transparent 24%),
                linear-gradient(180deg, #f8f4ee 0%, var(--bg) 100%);
            min-height: 100vh;
        }

        .shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 28px 24px 60px;
        }

        /* ── Header ── */
        .header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 26px;
        }

        .header-brand h1 {
            margin: 0;
            font-size: 2.4rem;
            font-weight: 700;
            letter-spacing: -0.045em;
            color: var(--text);
            line-height: 1;
        }

        .header-brand p {
            margin: 5px 0 0;
            font-size: 0.88rem;
            color: var(--muted);
            letter-spacing: 0.01em;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-shrink: 0;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s, box-shadow 0.15s, transform 0.1s;
            text-decoration: none;
            border: none;
            white-space: nowrap;
        }

        .btn:active { transform: translateY(1px); }

        .btn-primary {
            background: var(--accent);
            color: #fff;
        }

        .btn-primary:hover { background: #2a4c58; box-shadow: 0 3px 10px rgba(52,92,105,0.25); }

        .btn-ghost {
            background: var(--panel);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-ghost:hover { background: #fff; box-shadow: 0 2px 8px rgba(31,28,23,0.07); }

        .btn-sm {
            padding: 5px 12px;
            font-size: 0.82rem;
            border-radius: 8px;
        }

        .btn-xs {
            padding: 3px 9px;
            font-size: 0.78rem;
            border-radius: 7px;
        }

        .btn-danger {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid rgba(138,59,47,0.2);
        }

        .btn-danger:hover { background: #f5c8c0; }

        .btn-icon {
            padding: 4px 8px;
            font-size: 0.82rem;
            border-radius: 7px;
            line-height: 1;
        }

        /* ── Layout ── */
        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(300px, 1fr);
            gap: 20px;
            align-items: start;
        }

        .main-col { display: grid; gap: 20px; }
        .side-col { display: grid; gap: 20px; }

        /* ── Panels ── */
        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 14px 30px rgba(31, 28, 23, 0.06);
            overflow: hidden;
        }

        .panel-header {
            padding: 16px 20px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid var(--border);
        }

        .panel-header h2 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .panel-header-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .panel-body {
            padding: 18px 20px;
        }

        /* ── Project cards ── */
        .project-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .project-card {
            background: #fffdf8;
            border: 1px solid var(--border);
            border-radius: 13px;
            overflow: hidden;
            transition: box-shadow 0.15s, border-color 0.15s;
        }

        .project-card:hover {
            box-shadow: 0 6px 18px rgba(31,28,23,0.09);
            border-color: #c9bfaf;
        }

        .project-card-view {
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .project-card-edit {
            display: none;
            padding: 14px 16px;
            background: rgba(52,92,105,0.04);
            border-top: 1px solid var(--border);
        }

        .project-card-edit.open { display: block; }

        .project-card-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text);
        }

        .project-card-intent {
            font-size: 0.83rem;
            color: var(--muted);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .project-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 6px;
            border-top: 1px solid rgba(216,205,189,0.6);
            gap: 8px;
        }

        .project-card-meta {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .meta-item {
            font-size: 0.78rem;
            color: var(--muted);
        }

        /* ── Badges ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .badge-active    { background: var(--success-bg); color: var(--success); }
        .badge-paused    { background: var(--warning-bg); color: var(--warning); }
        .badge-blocked   { background: var(--danger-bg);  color: var(--danger); }
        .badge-complete  { background: var(--neutral-bg); color: var(--muted); }
        .badge-pending   { background: var(--warning-bg); color: var(--warning); }
        .badge-in-progress { background: var(--accent-soft); color: var(--accent); }
        .badge-neutral   { background: var(--neutral-bg); color: var(--muted); }
        .badge-draft     { background: #f0e8ff; color: #6b35a0; }

        /* ── Status dot ── */
        .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .dot-active    { background: var(--success); }
        .dot-rate-limited { background: #d97706; }
        .dot-degraded  { background: #ea580c; }
        .dot-disabled  { background: #a89e93; }
        .dot-unknown   { background: #a89e93; }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.55; transform: scale(1.35); }
        }

        .dot-active { animation: pulse 2.2s ease-in-out infinite; }

        /* ── Task rows ── */
        .task-list { display: grid; gap: 2px; }

        .task-row {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            transition: background 0.12s;
        }

        .task-row:hover { background: rgba(52,92,105,0.05); }

        .task-main { display: flex; flex-direction: column; gap: 3px; }

        .task-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text);
        }

        .task-meta {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .task-meta-item {
            font-size: 0.78rem;
            color: var(--muted);
        }

        .task-meta-sep { color: var(--border); font-size: 0.78rem; }

        .task-result {
            font-size: 0.78rem;
            color: var(--muted);
            font-style: italic;
            line-height: 1.4;
            margin-top: 2px;
        }

        .task-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        /* ── Inline selects ── */
        .form-select-sm {
            padding: 4px 8px;
            font-family: inherit;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text);
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 7px;
            cursor: pointer;
            outline: none;
            transition: border-color 0.15s;
        }

        .form-select-sm:focus { border-color: var(--accent); }

        /* ── Provider rows ── */
        .provider-list { display: grid; gap: 2px; }

        .provider-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px;
            border-radius: 10px;
            transition: background 0.12s;
        }

        .provider-row:hover { background: rgba(52,92,105,0.04); }

        .provider-info {
            flex: 1;
            min-width: 0;
        }

        .provider-name {
            font-weight: 600;
            font-size: 0.88rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .provider-usage {
            font-size: 0.77rem;
            color: var(--muted);
            margin-top: 1px;
        }

        .provider-badges { display: flex; gap: 5px; flex-shrink: 0; }

        /* ── Agent rows ── */
        .agent-list { display: grid; gap: 2px; }

        .agent-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 10px;
            transition: background 0.12s;
        }

        .agent-row:hover { background: rgba(52,92,105,0.04); }

        .agent-info { flex: 1; min-width: 0; }

        .agent-name {
            font-weight: 600;
            font-size: 0.88rem;
        }

        .agent-role {
            font-size: 0.78rem;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── Expandable cards ── */
        .expand-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(255,255,255,0.7);
            overflow: hidden;
        }

        .expand-card[open] { background: rgba(255,255,255,0.92); }

        .expand-summary {
            list-style: none;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            padding: 10px 14px;
            cursor: pointer;
        }

        .expand-summary::-webkit-details-marker { display: none; }

        .expand-title { display: grid; gap: 3px; }

        .expand-title strong { font-size: 0.9rem; }

        .expand-body {
            padding: 0 14px 14px;
            display: grid;
            gap: 10px;
            border-top: 1px solid var(--border);
        }

        .pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 2px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 3px 8px;
            background: var(--neutral-bg);
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 700;
            line-height: 1;
        }

        .pill-green { background: rgba(45,106,79,0.12); color: var(--success); }
        .pill-amber { background: rgba(154,103,0,0.12); color: var(--warning); }

        .expand-form { display: grid; gap: 8px; }

        .expand-form-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        /* ── Manage toggle ── */
        details.manage-toggle > summary {
            list-style: none;
            cursor: pointer;
        }

        details.manage-toggle > summary::-webkit-details-marker { display: none; }

        /* ── Heartbeat rows ── */
        .heartbeat-list { display: grid; gap: 4px; }

        .heartbeat-row {
            display: flex;
            flex-direction: column;
            border-radius: 10px;
            background: rgba(52,92,105,0.04);
            overflow: hidden;
            cursor: pointer;
        }

        .heartbeat-row:hover { background: rgba(52,92,105,0.08); }

        .heartbeat-row-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 14px;
        }

        .heartbeat-time {
            font-size: 0.82rem;
            color: var(--muted);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .heartbeat-detail {
            font-size: 0.82rem;
            color: var(--text);
        }

        .heartbeat-meta {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 2px;
        }

        .heartbeat-note {
            font-size: 0.78rem;
            color: var(--muted);
            font-style: italic;
            padding: 6px 0 0;
        }

        .heartbeat-detail-panel {
            display: none;
            padding: 0 14px 10px;
            border-top: 1px solid var(--border);
        }

        .heartbeat-detail-panel.open { display: block; }

        .heartbeat-section {
            margin-top: 8px;
        }

        .heartbeat-section-title {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .heartbeat-decision {
            font-size: 0.8rem;
            color: var(--text);
            padding: 4px 8px;
            background: rgba(255,255,255,0.7);
            border-radius: 6px;
            margin-bottom: 3px;
            line-height: 1.4;
        }

        .heartbeat-task-item {
            font-size: 0.78rem;
            color: var(--text);
            padding: 3px 8px;
            background: rgba(255,255,255,0.7);
            border-radius: 6px;
            margin-bottom: 3px;
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }

        .heartbeat-provider-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3px;
        }

        .heartbeat-provider-item {
            font-size: 0.76rem;
            color: var(--muted);
            padding: 3px 8px;
            background: rgba(255,255,255,0.7);
            border-radius: 6px;
        }

        /* ── Empty state ── */
        .empty {
            padding: 22px 0;
            text-align: center;
            color: var(--muted);
            font-size: 0.88rem;
        }

        /* ── Inline forms ── */
        .inline-form {
            display: none;
            border-top: 1px solid var(--border);
            padding: 16px 20px;
            background: rgba(52,92,105,0.03);
        }

        .inline-form.open { display: block; }

        .form-grid {
            display: grid;
            gap: 10px;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .form-row { display: grid; gap: 5px; }

        .form-row label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 8px 12px;
            font-family: inherit;
            font-size: 0.88rem;
            color: var(--text);
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 9px;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(52,92,105,0.12);
        }

        .form-textarea { resize: vertical; min-height: 64px; }

        .form-actions {
            display: flex;
            gap: 8px;
            padding-top: 4px;
        }

        /* ── Priority indicator ── */
        .priority-high   { color: var(--danger); font-size: 0.75rem; font-weight: 700; }
        .priority-normal { color: var(--muted);  font-size: 0.75rem; }
        .priority-low    { color: #a89e93;       font-size: 0.75rem; }

        /* ── Links ── */
        a { color: inherit; }

        .link-accent {
            color: var(--accent);
            font-weight: 600;
            font-size: 0.82rem;
            text-decoration: none;
            white-space: nowrap;
        }

        .link-accent:hover { text-decoration: underline; }

        /* ── Divider ── */
        .panel-divider {
            height: 1px;
            background: var(--border);
            margin: 0;
            opacity: 0.6;
        }

        /* ── Suggestion rows ── */
        .suggestion-list { display: grid; gap: 2px; }

        .suggestion-row {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: start;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            transition: background 0.12s;
        }

        .suggestion-row:hover { background: rgba(107,53,160,0.04); }

        .suggestion-main { display: flex; flex-direction: column; gap: 3px; }

        .suggestion-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text);
        }

        .suggestion-desc {
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.4;
        }

        .suggestion-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
            padding-top: 1px;
        }

        .btn-accept {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid rgba(45,106,79,0.2);
        }

        .btn-accept:hover { background: #b7e0ca; }

        /* ── Document rows ── */
        .doc-list { display: grid; gap: 2px; }

        .doc-row {
            display: flex;
            flex-direction: column;
            padding: 9px 14px;
            border-radius: 10px;
            transition: background 0.12s;
            cursor: pointer;
        }

        .doc-row:hover { background: rgba(52,92,105,0.04); }

        .doc-row-header {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .doc-title {
            flex: 1;
            font-weight: 600;
            font-size: 0.88rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .doc-meta {
            font-size: 0.76rem;
            color: var(--muted);
            margin-top: 2px;
            display: flex;
            gap: 6px;
        }

        .badge-filetype {
            background: var(--neutral-bg);
            color: var(--muted);
            padding: 1px 6px;
            border-radius: 5px;
            font-size: 0.7rem;
            font-weight: 700;
            font-family: monospace;
            letter-spacing: 0.03em;
        }

        .doc-content {
            display: none;
            margin-top: 8px;
            padding: 10px 12px;
            background: rgba(31,28,23,0.04);
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.78rem;
            line-height: 1.55;
            color: var(--text);
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 280px;
            overflow-y: auto;
        }

        .doc-content.open { display: block; }
    </style>
    @livewireStyles
</head>
<body>
<div class="shell">

    {{-- ── Header ── --}}
    <header class="header">
        <div class="header-brand">
            <h1>Clawra</h1>
            <p>Personal AI Orchestration</p>
        </div>
        <div class="header-actions">
            <form method="POST" action="/admin/heartbeat" style="margin:0">
                @csrf
                <button type="submit" class="btn btn-ghost">↺ Run Heartbeat</button>
            </form>
            <a href="/coordinator" class="btn btn-primary">Control Room →</a>
        </div>
    </header>

    <div class="layout">

        {{-- ══════════ MAIN COLUMN ══════════ --}}
        <div class="main-col">

            {{-- ── Chat ── --}}
            <div class="panel" style="overflow:hidden; padding:0;">
                <livewire:chat />
            </div>

            {{-- ── Projects ── --}}
            <div class="panel">
                <div class="panel-header">
                    <h2>Projects</h2>
                    <div class="panel-header-actions">
                        <span class="meta-item">{{ $projects->count() }} total</span>
                        <button class="btn btn-sm btn-primary" id="toggle-new-project">+ New Project</button>
                    </div>
                </div>

                {{-- New project form --}}
                <div class="inline-form" id="new-project-form">
                    <form method="POST" action="/admin/projects">
                        @csrf
                        <div class="form-grid">
                            <div class="form-grid-2">
                                <div class="form-row">
                                    <label for="proj-name">Name *</label>
                                    <input id="proj-name" name="name" type="text" class="form-input" placeholder="e.g. Clawra Core" required autofocus>
                                </div>
                                <div class="form-row">
                                    <label for="proj-status">Status</label>
                                    <select id="proj-status" name="status" class="form-select">
                                        <option value="active">Active</option>
                                        <option value="paused">Paused</option>
                                        <option value="blocked">Blocked</option>
                                        <option value="complete">Complete</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <label for="proj-intent">Current Intent</label>
                                <input id="proj-intent" name="current_intent" type="text" class="form-input" placeholder="What are you focused on right now?">
                            </div>
                            <div class="form-row">
                                <label for="proj-workspace">Workspace Path</label>
                                <input id="proj-workspace" name="workspace_path" type="text" class="form-input" placeholder="e.g. C:\Users\you\projects\my-project">
                            </div>
                            <div class="form-row">
                                <label for="proj-desc">Description</label>
                                <textarea id="proj-desc" name="description" class="form-textarea" placeholder="What is this project?"></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-sm">Save Project</button>
                                <button type="button" class="btn btn-ghost btn-sm" id="cancel-new-project">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="panel-body">
                    @forelse ($projects as $project)
                        @if ($loop->first)
                        <div class="project-grid">
                        @endif

                        <div class="project-card" id="project-card-{{ $project->id }}">
                            {{-- Card view --}}
                            <div class="project-card-view">
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    <span class="project-card-name">{{ $project->name }}</span>
                                    <span class="badge badge-{{ $project->status ?? 'neutral' }}">
                                        {{ $project->status ?? 'unknown' }}
                                    </span>
                                </div>

                                @if ($project->current_intent)
                                    <div class="project-card-intent">{{ $project->current_intent }}</div>
                                @elseif ($project->description)
                                    <div class="project-card-intent">{{ $project->description }}</div>
                                @endif
                                @if ($project->workspace_path)
                                    <div class="project-card-intent" style="font-family:monospace; font-size:0.76rem; color:var(--accent);">{{ $project->workspace_path }}</div>
                                @endif

                                <div class="project-card-footer">
                                    <div class="project-card-meta">
                                        <span class="meta-item">{{ $project->tasks_count }} {{ Str::plural('task', $project->tasks_count) }}</span>
                                        <span class="meta-item" data-ts="{{ $project->updated_at?->toISOString() }}">
                                            {{ $project->updated_at?->diffForHumans() }}
                                        </span>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <a href="/coordinator?project_id={{ $project->id }}" class="link-accent">Open →</a>
                                        <button type="button" class="btn btn-xs btn-ghost" onclick="toggleProjectEdit({{ $project->id }})">Edit</button>
                                        <form method="POST" action="/admin/projects/{{ $project->id }}" style="margin:0" onsubmit="return confirm('Delete {{ addslashes($project->name) }}? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Inline edit form --}}
                            <div class="project-card-edit" id="project-edit-{{ $project->id }}">
                                <form method="POST" action="/admin/projects/{{ $project->id }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="form-grid">
                                        <div class="form-grid-2">
                                            <div class="form-row">
                                                <label>Name *</label>
                                                <input name="name" type="text" class="form-input" value="{{ $project->name }}" required>
                                            </div>
                                            <div class="form-row">
                                                <label>Status</label>
                                                <select name="status" class="form-select">
                                                    @foreach (['active', 'paused', 'blocked', 'complete'] as $s)
                                                        <option value="{{ $s }}" @selected(($project->status ?? 'active') === $s)>{{ ucfirst($s) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <label>Current Intent</label>
                                            <input name="current_intent" type="text" class="form-input" value="{{ $project->current_intent }}">
                                        </div>
                                        <div class="form-row">
                                            <label>Workspace Path</label>
                                            <input name="workspace_path" type="text" class="form-input" value="{{ $project->workspace_path }}" placeholder="e.g. C:\Users\you\projects\my-project">
                                        </div>
                                        <div class="form-row">
                                            <label>Git Remote URL</label>
                                            <input name="git_remote_url" type="text" class="form-input" value="{{ $project->git_remote_url }}" placeholder="e.g. https://github.com/org/repo.git">
                                        </div>
                                        <div class="form-row">
                                            <label>Description</label>
                                            <textarea name="description" class="form-textarea">{{ $project->description }}</textarea>
                                        </div>
                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleProjectEdit({{ $project->id }})">Cancel</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        @if ($loop->last)
                        </div>
                        @endif
                    @empty
                        <div class="empty">No projects yet. Create one to get started.</div>
                    @endforelse
                </div>
            </div>

            {{-- ── Active Tasks ── --}}
            <div class="panel" style="overflow:hidden; padding:0;">
                <livewire:active-tasks />
            </div>

            {{-- ── Suggestions ── --}}
            @if ($draftTasks->isNotEmpty())
            <div class="panel">
                <div class="panel-header">
                    <h2>Suggestions</h2>
                    <div class="panel-header-actions">
                        <span class="badge badge-draft">{{ $draftTasks->count() }}</span>
                    </div>
                </div>
                <div class="panel-body" style="padding-top:8px; padding-bottom:8px;">
                    <div class="suggestion-list">
                        @foreach ($draftTasks as $task)
                            <div class="suggestion-row">
                                <div class="suggestion-main">
                                    <span class="suggestion-name">{{ Str::replaceFirst('Suggestion: ', '', $task->name) }}</span>
                                    <div class="task-meta">
                                        @if ($task->project)
                                            <a href="/coordinator?project_id={{ $task->project->id }}" class="task-meta-item" style="color:var(--accent); font-weight:600;">
                                                {{ $task->project->name }}
                                            </a>
                                        @endif
                                        @if ($task->description)
                                            <span class="task-meta-sep">·</span>
                                            <span class="suggestion-desc">{{ Str::limit($task->description, 120) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="suggestion-actions">
                                    <form method="POST" action="/admin/tasks/{{ $task->id }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="pending">
                                        <input type="hidden" name="name" value="{{ $task->name }}">
                                        <input type="hidden" name="description" value="{{ $task->description }}">
                                        <input type="hidden" name="priority" value="{{ $task->priority ?? 50 }}">
                                        <button type="submit" class="btn btn-xs btn-accept">Accept</button>
                                    </form>
                                    <form method="POST" action="/admin/tasks/{{ $task->id }}" onsubmit="return confirm('Dismiss this suggestion?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger">Dismiss</button>
                                    </form>
                                </div>
                            </div>
                            @if (!$loop->last)
                                <div class="panel-divider" style="margin: 0 14px;"></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>

        {{-- ══════════ SIDEBAR ══════════ --}}
        <div class="side-col">

            {{-- ── Providers ── --}}
            <div class="panel">
                <div class="panel-header">
                    <h2>Providers</h2>
                    <div class="panel-header-actions">
                        <span class="meta-item">{{ $providers->count() }} total</span>
                        <button class="btn btn-sm btn-primary" id="toggle-new-provider">+ New Provider</button>
                    </div>
                </div>

                <div class="inline-form" id="new-provider-form">
                    <form method="POST" action="/admin/providers">
                        @csrf
                        <div class="form-grid">
                            <div class="form-grid-2">
                                <div class="form-row">
                                    <label>Name *</label>
                                    <input name="name" type="text" class="form-input" placeholder="e.g. synthetic" required>
                                </div>
                                <div class="form-row">
                                    <label>Type *</label>
                                    <select name="type" class="form-select" required>
                                        @foreach (['subscription', 'hybrid', 'api-only', 'API-key-based', 'CLI-tool-based'] as $t)
                                            <option value="{{ $t }}">{{ $t }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-grid-2">
                                <div class="form-row">
                                    <label>API Protocol *</label>
                                    <select name="api_protocol" class="form-select" required>
                                        @foreach (['Anthropic-compatible', 'OpenAI-compatible', 'native'] as $p)
                                            <option value="{{ $p }}">{{ $p }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label>Status *</label>
                                    <select name="status" class="form-select" required>
                                        @foreach (['active', 'rate-limited', 'degraded', 'disabled'] as $s)
                                            <option value="{{ $s }}">{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <label>Capabilities (comma-separated)</label>
                                <input name="capability_tags_text" type="text" class="form-input" placeholder="e.g. chat, planning">
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-sm">Create Provider</button>
                                <button type="button" class="btn btn-ghost btn-sm" id="cancel-new-provider">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="panel-body" style="padding-top:8px; padding-bottom:8px; display:grid; gap:6px;">
                    @forelse ($providers as $provider)
                        @php
                            $dotClass = match($provider->status) {
                                'active'       => 'dot-active',
                                'rate-limited' => 'dot-rate-limited',
                                'degraded'     => 'dot-degraded',
                                'disabled'     => 'dot-disabled',
                                default        => 'dot-unknown',
                            };
                        @endphp
                        <details class="expand-card">
                            <summary class="expand-summary">
                                <div class="expand-title">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span class="dot {{ $dotClass }}"></span>
                                        <strong>{{ $provider->name }}</strong>
                                    </div>
                                    <div class="pill-row">
                                        <span class="pill {{ $provider->status === 'active' ? 'pill-green' : ($provider->status === 'rate-limited' ? 'pill-amber' : '') }}">{{ $provider->status }}</span>
                                        <span class="pill">{{ $provider->type }}</span>
                                        @if ($provider->routes->isNotEmpty())
                                            <span class="pill">{{ $provider->routes->count() }} {{ Str::plural('route', $provider->routes->count()) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="btn btn-xs btn-ghost">Edit</span>
                            </summary>
                            <div class="expand-body">
                                <form class="expand-form" method="POST" action="/admin/providers/{{ $provider->id }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="form-grid-2">
                                        <div class="form-row">
                                            <label>Name *</label>
                                            <input name="name" type="text" class="form-input" value="{{ $provider->name }}" required>
                                        </div>
                                        <div class="form-row">
                                            <label>Type *</label>
                                            <select name="type" class="form-select" required>
                                                @foreach (['subscription', 'hybrid', 'api-only', 'API-key-based', 'CLI-tool-based'] as $t)
                                                    <option value="{{ $t }}" @selected($provider->type === $t)>{{ $t }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-grid-2">
                                        <div class="form-row">
                                            <label>API Protocol *</label>
                                            <select name="api_protocol" class="form-select" required>
                                                @foreach (['Anthropic-compatible', 'OpenAI-compatible', 'native'] as $p)
                                                    <option value="{{ $p }}" @selected($provider->api_protocol === $p)>{{ $p }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-row">
                                            <label>Status *</label>
                                            <select name="status" class="form-select" required>
                                                @foreach (['active', 'rate-limited', 'degraded', 'disabled'] as $s)
                                                    <option value="{{ $s }}" @selected($provider->status === $s)>{{ $s }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <label>Capabilities (comma-separated)</label>
                                        <input name="capability_tags_text" type="text" class="form-input" value="{{ implode(', ', $provider->capability_tags ?? []) }}">
                                    </div>
                                    <div class="form-row">
                                        <label>Usage Snapshot JSON</label>
                                        <textarea name="usage_snapshot_text" class="form-textarea" style="font-family:monospace; font-size:0.8rem; min-height:48px;">{{ json_encode($provider->usage_snapshot ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                                    </div>
                                    <div class="expand-form-actions">
                                        <button type="submit" class="btn btn-primary btn-sm">Save Provider</button>
                                    </div>
                                </form>

                                {{-- Routes --}}
                                @if ($provider->routes->isNotEmpty())
                                    <div style="display:grid; gap:6px; padding-top:4px; border-top:1px solid var(--border);">
                                        <span class="meta-item" style="font-weight:700; padding-top:4px;">Routes</span>
                                        @foreach ($provider->routes as $route)
                                            <details class="expand-card" style="border-color:rgba(216,205,189,0.5);">
                                                <summary class="expand-summary" style="padding:8px 12px;">
                                                    <div class="expand-title">
                                                        <strong>{{ $route->name }}</strong>
                                                        <div class="pill-row">
                                                            <span class="pill {{ $route->status === 'active' ? 'pill-green' : '' }}">{{ $route->status }}</span>
                                                            <span class="pill">{{ $route->harness }}</span>
                                                            @if ($route->models->isNotEmpty())
                                                                <span class="pill">{{ $route->models->count() }} {{ Str::plural('model', $route->models->count()) }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <span class="btn btn-xs btn-ghost">Edit</span>
                                                </summary>
                                                <div class="expand-body">
                                                    <form class="expand-form" method="POST" action="/admin/provider-routes/{{ $route->id }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <div class="form-grid-2">
                                                            <div class="form-row">
                                                                <label>Name *</label>
                                                                <input name="name" type="text" class="form-input" value="{{ $route->name }}" required>
                                                            </div>
                                                            <div class="form-row">
                                                                <label>Harness *</label>
                                                                <select name="harness" class="form-select" required>
                                                                    @foreach (['laravel_ai', 'opencode', 'claude_code', 'codex'] as $h)
                                                                        <option value="{{ $h }}" @selected($route->harness === $h)>{{ $h }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-grid-2">
                                                            <div class="form-row">
                                                                <label>Auth Mode *</label>
                                                                <select name="auth_mode" class="form-select" required>
                                                                    @foreach (['api_key', 'chatgpt_oauth', 'provider_oauth'] as $a)
                                                                        <option value="{{ $a }}" @selected($route->auth_mode === $a)>{{ $a }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="form-row">
                                                                <label>Status *</label>
                                                                <select name="status" class="form-select" required>
                                                                    @foreach (['active', 'degraded', 'rate-limited', 'disabled'] as $s)
                                                                        <option value="{{ $s }}" @selected($route->status === $s)>{{ $s }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-row">
                                                            <label>Capabilities (comma-separated)</label>
                                                            <input name="capability_tags_text" type="text" class="form-input" value="{{ implode(', ', $route->capability_tags ?? []) }}">
                                                        </div>
                                                        <div class="form-row">
                                                            <label>Usage Snapshot JSON</label>
                                                            <textarea name="usage_snapshot_text" class="form-textarea" style="font-family:monospace; font-size:0.8rem; min-height:48px;">{{ json_encode($route->usage_snapshot ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                                                        </div>
                                                        <div class="expand-form-actions">
                                                            <label style="font-size:0.82rem; display:flex; align-items:center; gap:5px;">
                                                                <input type="checkbox" name="supports_tools" value="1" @checked($route->supports_tools)> Tools
                                                            </label>
                                                            <button type="submit" class="btn btn-primary btn-sm">Save Route</button>
                                                        </div>
                                                    </form>

                                                    {{-- Models under this route --}}
                                                    @if ($route->models->isNotEmpty())
                                                        <div style="display:grid; gap:4px; padding-top:4px; border-top:1px solid var(--border);">
                                                            <span class="meta-item" style="font-weight:700; padding-top:4px;">Models</span>
                                                            @foreach ($route->models as $pm)
                                                                <details class="expand-card" style="border-color:rgba(216,205,189,0.4);">
                                                                    <summary class="expand-summary" style="padding:7px 10px;">
                                                                        <div class="expand-title">
                                                                            <strong>{{ $pm->name }}</strong>
                                                                            <div class="pill-row">
                                                                                @if ($pm->is_default)<span class="pill pill-green">default</span>@endif
                                                                                <span class="pill {{ $pm->status === 'active' ? 'pill-green' : '' }}">{{ $pm->status }}</span>
                                                                                @if ($pm->external_name)<span class="pill">{{ $pm->external_name }}</span>@endif
                                                                            </div>
                                                                        </div>
                                                                        <span class="btn btn-xs btn-ghost">Edit</span>
                                                                    </summary>
                                                                    <div class="expand-body">
                                                                        <form class="expand-form" method="POST" action="/admin/provider-models/{{ $pm->id }}">
                                                                            @csrf
                                                                            @method('PATCH')
                                                                            <div class="form-grid-2">
                                                                                <div class="form-row">
                                                                                    <label>Name *</label>
                                                                                    <input name="name" type="text" class="form-input" value="{{ $pm->name }}" required>
                                                                                </div>
                                                                                <div class="form-row">
                                                                                    <label>External Name</label>
                                                                                    <input name="external_name" type="text" class="form-input" value="{{ $pm->external_name }}">
                                                                                </div>
                                                                            </div>
                                                                            <div class="form-row">
                                                                                <label>Capabilities (comma-separated)</label>
                                                                                <input name="capabilities_text" type="text" class="form-input" value="{{ implode(', ', $pm->capabilities ?? []) }}">
                                                                            </div>
                                                                            <div class="expand-form-actions">
                                                                                <select name="status" class="form-select" style="width:auto;" required>
                                                                                    @foreach (['active', 'disabled'] as $s)
                                                                                        <option value="{{ $s }}" @selected($pm->status === $s)>{{ $s }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                                <label style="font-size:0.82rem; display:flex; align-items:center; gap:5px;">
                                                                                    <input type="checkbox" name="is_default" value="1" @checked($pm->is_default)> Default
                                                                                </label>
                                                                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                                                            </div>
                                                                        </form>
                                                                        <form method="POST" action="/admin/provider-models/{{ $pm->id }}" onsubmit="return confirm('Delete model?')">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="btn btn-xs btn-danger">Delete Model</button>
                                                                        </form>
                                                                    </div>
                                                                </details>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    {{-- Add model --}}
                                                    <details class="expand-card" style="border-style:dashed; border-color:var(--border);">
                                                        <summary class="expand-summary" style="padding:7px 10px;">
                                                            <span style="font-size:0.82rem; color:var(--accent); font-weight:600;">+ Add Model</span>
                                                        </summary>
                                                        <div class="expand-body">
                                                            <form class="expand-form" method="POST" action="/admin/provider-models">
                                                                @csrf
                                                                <input type="hidden" name="provider_route_id" value="{{ $route->id }}">
                                                                <div class="form-grid-2">
                                                                    <div class="form-row">
                                                                        <label>Name *</label>
                                                                        <input name="name" type="text" class="form-input" placeholder="e.g. claude-sonnet" required>
                                                                    </div>
                                                                    <div class="form-row">
                                                                        <label>External Name</label>
                                                                        <input name="external_name" type="text" class="form-input" placeholder="API model ID">
                                                                    </div>
                                                                </div>
                                                                <div class="form-row">
                                                                    <label>Capabilities (comma-separated)</label>
                                                                    <input name="capabilities_text" type="text" class="form-input" placeholder="e.g. chat, planning">
                                                                </div>
                                                                <div class="expand-form-actions">
                                                                    <select name="status" class="form-select" style="width:auto;" required>
                                                                        <option value="active">active</option>
                                                                        <option value="disabled">disabled</option>
                                                                    </select>
                                                                    <label style="font-size:0.82rem; display:flex; align-items:center; gap:5px;">
                                                                        <input type="checkbox" name="is_default" value="1"> Default
                                                                    </label>
                                                                    <button type="submit" class="btn btn-primary btn-sm">Add Model</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </details>

                                                    <form method="POST" action="/admin/provider-routes/{{ $route->id }}" onsubmit="return confirm('Delete this route?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-xs btn-danger">Delete Route</button>
                                                    </form>
                                                </div>
                                            </details>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Add route --}}
                                <details class="expand-card" style="border-style:dashed; border-color:var(--border);">
                                    <summary class="expand-summary" style="padding:8px 12px;">
                                        <span style="font-size:0.82rem; color:var(--accent); font-weight:600;">+ Add Route</span>
                                    </summary>
                                    <div class="expand-body">
                                        <form class="expand-form" method="POST" action="/admin/provider-routes">
                                            @csrf
                                            <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                                            <div class="form-grid-2">
                                                <div class="form-row">
                                                    <label>Name *</label>
                                                    <input name="name" type="text" class="form-input" placeholder="e.g. synthetic-laravel-ai" required>
                                                </div>
                                                <div class="form-row">
                                                    <label>Harness *</label>
                                                    <select name="harness" class="form-select" required>
                                                        @foreach (['laravel_ai', 'opencode', 'claude_code', 'codex'] as $h)
                                                            <option value="{{ $h }}">{{ $h }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-grid-2">
                                                <div class="form-row">
                                                    <label>Auth Mode *</label>
                                                    <select name="auth_mode" class="form-select" required>
                                                        @foreach (['api_key', 'chatgpt_oauth', 'provider_oauth'] as $a)
                                                            <option value="{{ $a }}">{{ $a }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-row">
                                                    <label>Status *</label>
                                                    <select name="status" class="form-select" required>
                                                        @foreach (['active', 'degraded', 'rate-limited', 'disabled'] as $s)
                                                            <option value="{{ $s }}">{{ $s }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <label>Capabilities (comma-separated)</label>
                                                <input name="capability_tags_text" type="text" class="form-input" placeholder="e.g. chat, planning">
                                            </div>
                                            <div class="expand-form-actions">
                                                <button type="submit" class="btn btn-primary btn-sm">Add Route</button>
                                            </div>
                                        </form>
                                    </div>
                                </details>

                                <form method="POST" action="/admin/providers/{{ $provider->id }}" onsubmit="return confirm('Delete {{ addslashes($provider->name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger">Delete Provider</button>
                                </form>
                            </div>
                        </details>
                    @empty
                        <div class="empty">No providers configured.</div>
                    @endforelse
                </div>
            </div>

            {{-- ── Agents ── --}}
            <div class="panel">
                <div class="panel-header">
                    <h2>Agents</h2>
                    <div class="panel-header-actions">
                        <span class="meta-item">{{ $agents->count() }} total</span>
                        <button class="btn btn-sm btn-primary" id="toggle-new-agent">+ New Agent</button>
                    </div>
                </div>

                <div class="inline-form" id="new-agent-form">
                    <form method="POST" action="/admin/agents">
                        @csrf
                        <div class="form-grid">
                            <div class="form-grid-2">
                                <div class="form-row">
                                    <label>Name *</label>
                                    <input name="name" type="text" class="form-input" placeholder="e.g. Planner" required>
                                </div>
                                <div class="form-row">
                                    <label>Role *</label>
                                    <input name="role" type="text" class="form-input" placeholder="e.g. Planning agent" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <label>Description</label>
                                <textarea name="description" class="form-textarea" placeholder="What does this agent do?"></textarea>
                            </div>
                            <div class="form-row">
                                <label>Tools (comma-separated)</label>
                                <input name="tools_text" type="text" class="form-input" placeholder="e.g. web_search, code_exec">
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-sm">Create Agent</button>
                                <button type="button" class="btn btn-ghost btn-sm" id="cancel-new-agent">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="panel-body" style="padding-top:8px; padding-bottom:8px; display:grid; gap:6px;">
                    @forelse ($agents as $agent)
                        <details class="expand-card">
                            <summary class="expand-summary">
                                <div class="expand-title">
                                    <strong>{{ $agent->name }}</strong>
                                    <div class="pill-row">
                                        <span class="pill">{{ $agent->role }}</span>
                                        <span class="pill {{ ($agent->status ?? 'active') === 'active' ? 'pill-green' : '' }}">{{ $agent->status ?? 'active' }}</span>
                                        @if ($agent->runtimes->isNotEmpty())
                                            <span class="pill">{{ $agent->runtimes->count() }} {{ Str::plural('runtime', $agent->runtimes->count()) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="btn btn-xs btn-ghost">Edit</span>
                            </summary>
                            <div class="expand-body">
                                <form class="expand-form" method="POST" action="/admin/agents/{{ $agent->id }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="form-grid-2">
                                        <div class="form-row">
                                            <label>Name *</label>
                                            <input name="name" type="text" class="form-input" value="{{ $agent->name }}" required>
                                        </div>
                                        <div class="form-row">
                                            <label>Role *</label>
                                            <input name="role" type="text" class="form-input" value="{{ $agent->role }}" required>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <label>Description</label>
                                        <textarea name="description" class="form-textarea">{{ $agent->description }}</textarea>
                                    </div>
                                    <div class="form-row">
                                        <label>Tools (comma-separated)</label>
                                        <input name="tools_text" type="text" class="form-input" value="{{ implode(', ', $agent->tools ?? []) }}">
                                    </div>
                                    <div class="expand-form-actions">
                                        <button type="submit" class="btn btn-primary btn-sm">Save Agent</button>
                                    </div>
                                </form>

                                {{-- Runtimes --}}
                                @if ($agent->runtimes->isNotEmpty())
                                    <div style="display:grid; gap:6px; padding-top:4px; border-top:1px solid var(--border);">
                                        <span class="meta-item" style="font-weight:700; padding-top:4px;">Runtimes</span>
                                        @foreach ($agent->runtimes->sortByDesc('is_default') as $runtime)
                                            <details class="expand-card" style="border-color:rgba(216,205,189,0.5);">
                                                <summary class="expand-summary" style="padding:8px 12px;">
                                                    <div class="expand-title">
                                                        <strong>{{ $runtime->name }}</strong>
                                                        <div class="pill-row">
                                                            @if ($runtime->is_default)<span class="pill pill-green">default</span>@endif
                                                            @if ($runtime->saves_documents)<span class="pill pill-green">saves docs</span>@endif
                                                            <span class="pill">{{ $runtime->harness }}</span>
                                                            <span class="pill">{{ $runtime->runtime_ref }}</span>
                                                            @if ($runtime->route)<span class="pill">{{ $runtime->route->name }}</span>@endif
                                                            @if ($runtime->model)<span class="pill">{{ $runtime->model->name }}</span>@endif
                                                        </div>
                                                    </div>
                                                    <span class="btn btn-xs btn-ghost">Edit</span>
                                                </summary>
                                                <div class="expand-body">
                                                    <form class="expand-form" method="POST" action="/admin/agent-runtimes/{{ $runtime->id }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <div class="form-grid-2">
                                                            <div class="form-row">
                                                                <label>Name *</label>
                                                                <input name="name" type="text" class="form-input" value="{{ $runtime->name }}" required>
                                                            </div>
                                                            <div class="form-row">
                                                                <label>Harness *</label>
                                                                <select name="harness" class="form-select" required>
                                                                    @foreach (['laravel_ai', 'opencode', 'claude_code', 'codex'] as $h)
                                                                        <option value="{{ $h }}" @selected($runtime->harness === $h)>{{ $h }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-grid-2">
                                                            <div class="form-row">
                                                                <label>Runtime Type *</label>
                                                                <input name="runtime_type" type="text" class="form-input" value="{{ $runtime->runtime_type }}" required>
                                                            </div>
                                                            <div class="form-row">
                                                                <label>Runtime Ref *</label>
                                                                <input name="runtime_ref" type="text" class="form-input" value="{{ $runtime->runtime_ref }}" required>
                                                            </div>
                                                        </div>
                                                        <div class="form-grid-2">
                                                            <div class="form-row">
                                                                <label>Primary Route</label>
                                                                <select name="provider_route_id" class="form-select js-route-select" data-model-select="provider_model_id-{{ $runtime->id }}">
                                                                    <option value="">— None —</option>
                                                                    @foreach ($providerRoutes as $route)
                                                                        <option value="{{ $route->id }}" @selected($runtime->provider_route_id === $route->id)>{{ $route->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="form-row">
                                                                <label>Primary Model</label>
                                                                <select name="provider_model_id" id="provider_model_id-{{ $runtime->id }}" class="form-select">
                                                                    <option value="">— None —</option>
                                                                    @foreach ($providerModels as $pm)
                                                                        <option value="{{ $pm->id }}" data-route-id="{{ $pm->provider_route_id }}" @selected($runtime->provider_model_id === $pm->id)>{{ $pm->route->name }} · {{ $pm->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-grid-2">
                                                            <div class="form-row">
                                                                <label>Fallback Route</label>
                                                                <select name="fallback_provider_route_id" class="form-select js-route-select" data-model-select="fallback_provider_model_id-{{ $runtime->id }}">
                                                                    <option value="">— None —</option>
                                                                    @foreach ($providerRoutes as $route)
                                                                        <option value="{{ $route->id }}" @selected($runtime->fallback_provider_route_id === $route->id)>{{ $route->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="form-row">
                                                                <label>Fallback Model</label>
                                                                <select name="fallback_provider_model_id" id="fallback_provider_model_id-{{ $runtime->id }}" class="form-select">
                                                                    <option value="">— None —</option>
                                                                    @foreach ($providerModels as $pm)
                                                                        <option value="{{ $pm->id }}" data-route-id="{{ $pm->provider_route_id }}" @selected($runtime->fallback_provider_model_id === $pm->id)>{{ $pm->route->name }} · {{ $pm->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-row">
                                                            <label>Tools (comma-separated)</label>
                                                            <input name="tools_text" type="text" class="form-input" value="{{ implode(', ', $runtime->tools ?? []) }}">
                                                        </div>
                                                        <div class="form-row">
                                                            <label>Config JSON</label>
                                                            <textarea name="config_text" class="form-textarea" style="font-family:monospace; font-size:0.8rem;">{{ json_encode($runtime->config ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                                                        </div>
                                                        <div class="expand-form-actions">
                                                            <select name="status" class="form-select" style="width:auto;" required>
                                                                @foreach (['active', 'disabled'] as $s)
                                                                    <option value="{{ $s }}" @selected($runtime->status === $s)>{{ $s }}</option>
                                                                @endforeach
                                                            </select>
                                                            <label style="font-size:0.82rem; display:flex; align-items:center; gap:5px;">
                                                                <input type="checkbox" name="is_default" value="1" @checked($runtime->is_default)> Default
                                                            </label>
                                                            <label style="font-size:0.82rem; display:flex; align-items:center; gap:5px;">
                                                                <input type="checkbox" name="saves_documents" value="1" @checked($runtime->saves_documents)> Saves docs
                                                            </label>
                                                            <label style="font-size:0.82rem; display:flex; align-items:center; gap:5px;">
                                                                <input type="checkbox" name="sandboxed" value="1" @checked($runtime->sandboxed)> Sandboxed
                                                            </label>
                                                            <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                                        </div>
                                                    </form>
                                                    <form method="POST" action="/admin/agent-runtimes/{{ $runtime->id }}" onsubmit="return confirm('Delete this runtime?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-xs btn-danger">Delete Runtime</button>
                                                    </form>
                                                </div>
                                            </details>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Add runtime form --}}
                                <details class="expand-card" style="border-style:dashed; border-color:var(--border);">
                                    <summary class="expand-summary" style="padding:8px 12px;">
                                        <span style="font-size:0.82rem; color:var(--accent); font-weight:600;">+ Add Runtime</span>
                                    </summary>
                                    <div class="expand-body">
                                        <form class="expand-form" method="POST" action="/admin/agent-runtimes">
                                            @csrf
                                            <input type="hidden" name="agent_id" value="{{ $agent->id }}">
                                            <div class="form-grid-2">
                                                <div class="form-row">
                                                    <label>Name *</label>
                                                    <input name="name" type="text" class="form-input" placeholder="e.g. planner-opencode" required>
                                                </div>
                                                <div class="form-row">
                                                    <label>Harness *</label>
                                                    <select name="harness" class="form-select" required>
                                                        @foreach (['laravel_ai', 'opencode', 'claude_code', 'codex'] as $h)
                                                            <option value="{{ $h }}">{{ $h }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-grid-2">
                                                <div class="form-row">
                                                    <label>Runtime Type *</label>
                                                    <input name="runtime_type" type="text" class="form-input" value="laravel_class" required>
                                                </div>
                                                <div class="form-row">
                                                    <label>Runtime Ref *</label>
                                                    <input name="runtime_ref" type="text" class="form-input" placeholder="agent id or class" required>
                                                </div>
                                            </div>
                                            <div class="form-grid-2">
                                                <div class="form-row">
                                                    <label>Primary Route</label>
                                                    <select name="provider_route_id" class="form-select js-route-select" data-model-select="new-provider_model_id-{{ $agent->id }}">
                                                        <option value="">— None —</option>
                                                        @foreach ($providerRoutes as $route)
                                                            <option value="{{ $route->id }}">{{ $route->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-row">
                                                    <label>Primary Model</label>
                                                    <select name="provider_model_id" id="new-provider_model_id-{{ $agent->id }}" class="form-select">
                                                        <option value="">— None —</option>
                                                        @foreach ($providerModels as $pm)
                                                            <option value="{{ $pm->id }}" data-route-id="{{ $pm->provider_route_id }}">{{ $pm->route->name }} · {{ $pm->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="expand-form-actions">
                                                <select name="status" class="form-select" style="width:auto;" required>
                                                    <option value="active">active</option>
                                                    <option value="disabled">disabled</option>
                                                </select>
                                                <label style="font-size:0.82rem; display:flex; align-items:center; gap:5px;">
                                                    <input type="checkbox" name="is_default" value="1"> Default
                                                </label>
                                                <label style="font-size:0.82rem; display:flex; align-items:center; gap:5px;">
                                                    <input type="checkbox" name="saves_documents" value="1"> Saves docs
                                                </label>
                                                <button type="submit" class="btn btn-primary btn-sm">Add Runtime</button>
                                            </div>
                                        </form>
                                    </div>
                                </details>

                                <form method="POST" action="/admin/agents/{{ $agent->id }}" onsubmit="return confirm('Delete {{ addslashes($agent->name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger">Delete Agent</button>
                                </form>
                            </div>
                        </details>
                    @empty
                        <div class="empty">No agents configured.</div>
                    @endforelse
                </div>
            </div>

            {{-- ── Workflows ── --}}
            <div class="panel">
                <div class="panel-header">
                    <h2>Workflows</h2>
                    <div class="panel-header-actions">
                        <span class="meta-item">{{ $workflows->count() }} total</span>
                        <button class="btn btn-sm btn-primary" id="toggle-new-workflow">+ New Workflow</button>
                    </div>
                </div>

                <div class="inline-form" id="new-workflow-form">
                    <form method="POST" action="/admin/workflows">
                        @csrf
                        <div class="form-grid">
                            <div class="form-row">
                                <label>Name *</label>
                                <input name="name" type="text" class="form-input" placeholder="e.g. Research workflow" required>
                            </div>
                            <div class="form-row">
                                <label>Description</label>
                                <textarea name="description" class="form-textarea" placeholder="What does this workflow do?"></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-sm">Create Workflow</button>
                                <button type="button" class="btn btn-ghost btn-sm" id="cancel-new-workflow">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="panel-body" style="padding-top:8px; padding-bottom:8px;">
                    @forelse ($workflows as $workflow)
                        <div class="provider-row" style="flex-direction:column; align-items:flex-start; gap:4px;">
                            <div style="display:flex; align-items:center; justify-content:space-between; width:100%;">
                                <span class="provider-name">{{ $workflow->name }}</span>
                                <span class="badge badge-neutral">{{ count($workflow->steps ?? []) }} steps</span>
                            </div>
                            @if ($workflow->description)
                                <div class="provider-usage">{{ Str::limit($workflow->description, 80) }}</div>
                            @endif
                        </div>
                        @if (!$loop->last)
                            <div class="panel-divider" style="margin: 2px 14px;"></div>
                        @endif
                    @empty
                        <div class="empty">No workflows configured.</div>
                    @endforelse
                </div>
            </div>

            {{-- ── Recent Documents ── --}}
            @if ($recentDocuments->isNotEmpty())
            <div class="panel">
                <div class="panel-header">
                    <h2>Documents</h2>
                    <span class="meta-item">{{ $recentDocuments->count() }} recent</span>
                </div>
                <div class="panel-body" style="padding-top:8px; padding-bottom:8px;">
                    <div class="doc-list">
                        @foreach ($recentDocuments as $doc)
                            <div class="doc-row" onclick="toggleDoc({{ $doc->id }})">
                                <div class="doc-row-header">
                                    <span class="doc-title">{{ $doc->title }}</span>
                                    @if ($doc->file_type)
                                        <span class="badge-filetype">{{ $doc->file_type }}</span>
                                    @endif
                                </div>
                                <div class="doc-meta">
                                    @if ($doc->project)
                                        <span>{{ $doc->project->name }}</span>
                                        <span>·</span>
                                    @endif
                                    <span data-ts="{{ $doc->created_at?->toISOString() }}">{{ $doc->created_at?->diffForHumans() }}</span>
                                </div>
                                @if ($doc->content)
                                    <div class="doc-content" id="doc-content-{{ $doc->id }}">{{ $doc->content }}</div>
                                @endif
                            </div>
                            @if (!$loop->last)
                                <div class="panel-divider" style="margin: 2px 14px;"></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Recent Heartbeats ── --}}
            <div class="panel">
                <div class="panel-header">
                    <h2>Heartbeats</h2>
                </div>
                <livewire:recent-heartbeats />
            </div>

        </div>
    </div>
</div>

<script>
    // ── New project form toggle
    const toggleProjectBtn = document.getElementById('toggle-new-project');
    const cancelProjectBtn = document.getElementById('cancel-new-project');
    const newProjectForm   = document.getElementById('new-project-form');

    toggleProjectBtn?.addEventListener('click', () => {
        newProjectForm.classList.toggle('open');
        if (newProjectForm.classList.contains('open')) {
            document.getElementById('proj-name')?.focus();
        }
    });

    cancelProjectBtn?.addEventListener('click', () => {
        newProjectForm.classList.remove('open');
    });

    // ── Project inline edit toggle
    function toggleProjectEdit(id) {
        const editDiv = document.getElementById('project-edit-' + id);
        if (!editDiv) return;
        editDiv.classList.toggle('open');
    }

    // ── New agent form toggle
    document.getElementById('toggle-new-agent')?.addEventListener('click', () => {
        document.getElementById('new-agent-form').classList.toggle('open');
    });
    document.getElementById('cancel-new-agent')?.addEventListener('click', () => {
        document.getElementById('new-agent-form').classList.remove('open');
    });

    // ── New provider form toggle
    document.getElementById('toggle-new-provider')?.addEventListener('click', () => {
        document.getElementById('new-provider-form').classList.toggle('open');
    });
    document.getElementById('cancel-new-provider')?.addEventListener('click', () => {
        document.getElementById('new-provider-form').classList.remove('open');
    });

    // ── New workflow form toggle
    document.getElementById('toggle-new-workflow')?.addEventListener('click', () => {
        document.getElementById('new-workflow-form').classList.toggle('open');
    });
    document.getElementById('cancel-new-workflow')?.addEventListener('click', () => {
        document.getElementById('new-workflow-form').classList.remove('open');
    });

    // ── Document content toggle
    function toggleDoc(id) {
        const el = document.getElementById('doc-content-' + id);
        if (!el) return;
        el.classList.toggle('open');
    }

    // ── Relative timestamps
    function timeAgo(iso) {
        const d = new Date(iso);
        if (isNaN(d)) return '';
        const secs = Math.floor((Date.now() - d) / 1000);
        if (secs < 60)    return 'just now';
        if (secs < 3600)  return Math.floor(secs / 60) + 'm ago';
        if (secs < 86400) return Math.floor(secs / 3600) + 'h ago';
        return Math.floor(secs / 86400) + 'd ago';
    }

    document.querySelectorAll('[data-ts]').forEach(el => {
        const ts = el.dataset.ts;
        if (ts) el.textContent = timeAgo(ts);
    });

    // ── Route → Model dependent filtering
    function bindRouteModelFilter(routeSelect) {
        const modelSelectId = routeSelect.dataset.modelSelect;
        if (!modelSelectId) return;
        const modelSelect = document.getElementById(modelSelectId);
        if (!modelSelect) return;

        const applyFilter = () => {
            const routeId = routeSelect.value;
            modelSelect.querySelectorAll('option[data-route-id]').forEach(opt => {
                opt.hidden = routeId !== '' && opt.dataset.routeId !== routeId;
            });
            if (modelSelect.selectedOptions[0]?.hidden) {
                modelSelect.value = '';
            }
        };

        routeSelect.addEventListener('change', applyFilter);
        applyFilter();
    }

    document.querySelectorAll('select.js-route-select').forEach(bindRouteModelFilter);
</script>
@livewireScripts
</body>
</html>
