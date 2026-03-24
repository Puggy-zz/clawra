# Clawra — Agent Roster & Architecture

**Last Updated:** 2026-03-23

---

## Agent Roster

| Agent | Role | Runs Via | Queue | Sandbox | Primary Model | Fallback |
|---|---|---|---|---|---|---|
| Coordinator | Routing, decomposition, project state, user interaction | Laravel AI SDK | No (synchronous) | No | DeepSeek-V3-0324 (synthetic.new) | Gemini API (free tier) |
| Planner | Project breakdown, spec writing, implementation plans | Laravel AI SDK | No (synchronous) | No | Kimi-K2-Instruct (synthetic.new) | DeepSeek-V3 |
| Researcher | Web search, summarisation, fact-finding | Laravel AI SDK + synthetic.new /search | Yes (low priority) | Yes | synthetic.new /search | Gemini |
| Developer | Code generation via harness (opencode etc.) in sandbox | Harness | Yes (low priority) | Yes | opencode (model-agnostic) | configurable |
| Test Writer | Writes failing tests per spec | Harness | Yes (low priority) | Yes | opencode (model-agnostic) | configurable |
| Reviewer | Code review, test validation, spec compliance | Harness | Yes (low priority) | Yes | Qwen3-Coder (synthetic.new) | DeepSeek-R1 |
| Git | Merge to main, conflict resolution, push to remote, trigger re-index | Process facade | Yes (low priority) | No | — | — |
| Voice Interface | STT/TTS handling, wake word bridge | Local (Whisper + Porcupine) | No | No | Local | API fallback |

---

## Conversation Flow

### Standard Request
```
User → Coordinator → simple task → creates task directly → queued job
```

### Complex Request (Planning Required)
```
User → Coordinator → "This needs a plan — want me to start planning?" → User confirms
  → Planner (back and forth, streaming, synchronous)
  → Plan saved to DB (Document)
  → User reviews plan in UI
      → New project? → User selects project folder
          → System sets workspace_path, runs setup steps (git init, env, Dockerfile)
          → Plan converted → Tasks (structured, deterministic from planner output)
      → Existing project? → Plan converted → Tasks directly
  → Tasks queued → sandbox agents
```

The coordinator decides unilaterally whether planning is needed and asks the user for confirmation before handing off. The planner uses **structured output** so the plan format doubles as the task decomposition schema — human-readable for review, machine-parseable for task creation.

---

## TDD Task Workflow

All tasks within a project share **one persistent sandbox** for the duration of the workflow. Subtasks run sequentially inside it.

```
Test Writer → Developer → Reviewer → Git Agent → [codebase re-index]
                 ↑______________|
               (failure feedback loop, up to configurable max iterations)
```

1. **Test Writer** — writes failing Pest tests against the spec and acceptance criteria
2. **Developer** — implements until tests pass
3. **Reviewer** — inspects diff and test results against spec; runs full test suite; approves, requests changes (returns to Developer), or escalates to user
4. **Git Agent** — merges feature branch to main, pushes to remote, triggers codebase index update

---

## Agent Details

### Coordinator
- Always available, always responsive — no queue, no sandbox
- Reads ProviderRegistry before every dispatch decision
- Maintains active concurrency counters per model in ProviderRegistry
- Confirms with user before handing off to Planner
- Escalates to user when Git Agent encounters unresolvable conflicts

### Planner
- Synchronous — user waits for output (streaming to UI)
- No sandbox needed: reads from **indexed codebase context** (vector search over embeddings) for existing projects
- Writes plan as a structured Document to the database — no filesystem access required
- Plan format enforces structure (milestones, goals, tasks, acceptance criteria) to ensure nothing is missed and to enable deterministic plan→task conversion
- May use additional harnesses (opencode, Claude Code) for deep code analysis when needed — in that case a sandbox or worktree is required
- Index location decision deferred to Phase 2 (depends on indexer choice)

### Researcher
- Runs in a sandbox: prompt injection protection, and may need to create a git branch and commit research documents to the project
- Lower priority — research is assumed to take time; user is not blocked waiting for it
- Saves results as Documents attached to the relevant project
- General research (not project-specific) saved to `{app}/SavedDocuments/`

### Developer / Test Writer / Reviewer
- Share one persistent sandbox per task (one after another, not parallel)
- Sandbox persists across the full task workflow — keeps packages, build artefacts, Docker image cache warm
- Harness-driven (opencode or other CLI tools inside the sandbox)
- Model selection is harness-agnostic; harness model can be switched mid-task for fallback

### Git Agent
- Runs directly via Laravel's Process facade — no sandbox needed (pure git operations on host filesystem)
- Operations: create merge commit, resolve conflicts (auto where possible), push to remote
- Escalates to user on unresolvable conflicts rather than proceeding
- On successful merge: triggers codebase index update for the project
- Runs after every Reviewer approval

---

## Queue Structure

```
coordinator  → synchronous (no queue)
high         → planner (when queued), interactive requests
low          → researcher, developer, test writer, reviewer, git agent
```

- `low` queue workers run with `--concurrency=N` to cap Docker sandbox overhead
- `high` queue runs with higher concurrency (LLM calls only, no Docker cost)
- ProviderRegistry tracks **active concurrent requests per model** (not just rate limit windows) to enforce synthetic.new's per-model concurrency limits before any API call

---

## Storage Layout

```
{workspace_path}                        e.g. D:/Projects/MyOtherProject/
  → project code (git repository)

{app}/ProjectData/{project_id}/
  → codebase index (location TBD — may move inside workspace if indexer supports it)
  → project-specific documents (plans, specs, research tied to this project)

{app}/SavedDocuments/
  → general research not tied to a specific project
```

`workspace_path` is stored on the Project model. `ProjectData` path is derived from the app base path + project ID — not stored separately.

---

## Codebase Indexing

- **New project:** index triggered after the first task merges to main (typically "Install Laravel" or equivalent framework setup — the first task everything else depends on)
- **Existing project added:** index triggered immediately on `workspace_path` confirmation
- **Ongoing:** index updated by Git Agent after every successful merge to main
- **Index always reflects main** — no mid-task re-indexing; developer works in a sandbox branch, planner reads from main's index
- Index location (inside `ProjectData` vs inside `workspace`) deferred to Phase 2, dependent on indexer choice

---

## Task Dependencies

Tasks support a `depends_on` relationship. For new projects, early setup tasks (e.g. "Install Laravel") are prerequisites for subsequent tasks. Dependent tasks remain in `pending` state and are not queued until their dependencies reach `completed` status.

---

## Concurrency Management

Two separate constraints, handled differently:

| Constraint | Applies To | Enforcement |
|---|---|---|
| Sandbox resource cost | Developer, Test Writer, Reviewer, Researcher | Queue worker `--concurrency` limit on `low` queue |
| synthetic.new per-model concurrency (1 req/model, 2 for small models) | All agents using synthetic.new | ProviderRegistry active-request counter checked before every API call |

The ProviderRegistry increments a counter when a request starts and decrements on completion. If no concurrency slot is available for the required model, the task waits or falls back to the configured fallback provider.
