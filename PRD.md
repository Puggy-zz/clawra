# PRD: Clawra — Personal AI Orchestration System
**Codename:** Clawra  
**Status:** Draft v0.4  
**Last Updated:** 2026-03-06

---

## 1. Overview

Clawra is a personal AI assistant and agent orchestration platform built on Laravel, distributed as a NativePHP desktop application for Windows. It accepts requests via voice or text, decomposes them into tasks, and delegates those tasks to a team of specialised agents. A coordinator agent — also named Clawra — manages task routing, tracks project state, and makes inference-aware scheduling decisions based on real-time knowledge of available API limits and subscription windows.

The name is a reference to openclaw and Laravel.

This is a personal tool, built to scratch a specific itch. The competitor landscape for AI orchestration and coding agent products has exploded recently, but existing solutions share common problems: subscription fees on top of already-paid inference costs, poor fit for a rate-limited subscription inference model, workflow assumptions that don't match this use case, or in some cases (notably openclaw) enough public security scrutiny that they represent an active target. A custom-built personal tool avoids all of these. The architecture is clean enough that it could be shared later, but that is not a design constraint.

The system runs entirely locally on Windows via NativePHP. PostgreSQL and Redis run natively on Windows. Docker Desktop for Windows provides the sandbox infrastructure via Docker Sandboxes (microVM-based).

---

## 2. Goals

- Reduce friction in managing multiple long-running personal software projects simultaneously
- Leverage existing inference subscriptions (synthetic.new, ChatGPT Plus, Claude Max) as effectively as possible without pay-per-token API costs for the primary workload
- Support voice-driven interaction for ambient, hands-free control
- Keep agents working in the background with minimal human supervision
- Maintain coherent project state across sessions and agent failures
- Enforce structured, workflow-driven task execution with automated review to ensure quality and completeness of agent output

## 3. Non-Goals (v1)

- Multi-tenant access, billing infrastructure, or commercial distribution
- Real-time collaboration or shared workspaces
- A polished consumer UI
- Fine-tuned or locally-hosted models
- Full autonomy — human review checkpoints are acceptable and expected
- Cloud sandbox backends — everything runs locally via Docker Sandboxes

---

## 4. Why Build This

The personal AI assistant and coding agent space has seen a wave of new products in early 2026. Most share the same fundamental problems for this use case:

- They add a subscription fee on top of inference costs you're already paying
- They assume API-key-based pay-per-token inference, not subscription-based rate-limited models like synthetic.new
- They don't support the specific combination of tools (opencode, Docker Sandboxes, synthetic.new) that makes sense here
- High-profile tools like openclaw have enough public attention and reported security issues that they are actively targeted

Building a personal system means full control over inference routing, sandbox model, rate limit handling, and project state — with no third party between you and your code.

---

## 5. Core Concepts

### 5.1 Clawra — The Coordinator Agent

Clawra is the central coordinator. It receives all user input, maintains awareness of all active projects, knows the current state of every inference provider, and decides what to delegate to whom. It does not do heavy work itself; its job is decomposition, routing, scheduling, and summarisation.

The coordinator agent runs directly through the Laravel AI SDK and does not operate within a sandboxed environment. It is a persistent, first-class part of the application — always available, always responsive.

The coordinator's primary inference is synthetic.new (DeepSeek-V3-0324). Because synthetic.new enforces rate and concurrency limits, the coordinator must remain responsive even when those limits are hit. The fallback for the coordinator is the **Gemini API free tier** — a zero-cost fallback that allows Clawra to continue responding to user input, managing project state, and queuing tasks for other agents even when the synthetic.new window is exhausted. The Laravel AI SDK's model fallback mechanism may handle this automatically; whether it is sufficient or requires explicit orchestration logic is a point to validate in Phase 0.

### 5.2 Inference Plans & Provider Registry

The system maintains a registry of configured inference providers. The registry stores the following per-provider metadata:

- Provider type (API-key-based / CLI-tool-based)
- API protocol (OpenAI-compatible / Anthropic-compatible / native)
- Current usage snapshot (requests used, requests remaining, window reset time)
- Rate limit structure (requests-per-window, concurrency limits, tokens-per-minute where applicable)
- Concurrency model — for synthetic.new: 1 concurrent call per model per $30 pack; additional packs increase concurrency per model
- Estimated cost/weight per task type
- Capability tags (e.g. `code-generation`, `long-context`, `reasoning`, `web-search`, `chat`, `embeddings`)
- Priority/preference rank for each capability tag
- Status (active, rate-limited, degraded, disabled)

The coordinator reads this registry before every delegation decision. Logging from all agents feeds back into it to keep usage estimates current.

**synthetic.new specifics:** Both OpenAI-compatible and Anthropic-compatible endpoints are provided. The subscription model is $30/pack/month, yielding 135 requests per 5-hour rolling window and 1 concurrent request per model per pack. Additional packs increase both limits and concurrency proportionally. Embedding requests (nomic-ai/nomic-embed-text-v1.5) are included at no additional charge and do not count against the request rate limit. Rate limits are tracked locally via logs since synthetic.new does not expose a usage API.

**OpenAI / Anthropic API:** Both expose usage endpoints. The system polls these on a configurable interval to maintain an accurate picture of remaining quota.

**Task priority and fallback inference:** Coding tasks default to **low priority**. High-priority coding tasks (configurable per project or per task) can use a fallback inference provider when synthetic.new is unavailable; this is user-configurable. opencode supports switching models on the fly, which means a coding session can continue on a fallback model mid-task without restarting the sandbox. The specifics of how opencode's model-switching integrates with the fallback configuration are a point to validate in Phase 1.

### 5.3 Agent Roster & Model Assignment

Each agent is assigned a distinct primary model to avoid synthetic.new concurrency conflicts. The table below reflects defaults for a single-pack synthetic.new subscription.

| Agent | Role | Default Model | Fallback |
|---|---|---|---|
| Clawra (Coordinator) | Routing, decomposition, project state | DeepSeek-V3-0324 (synthetic.new) | Gemini API (free tier) |
| Planner | Project breakdown, spec writing, implementation plans | Kimi-K2-Instruct (synthetic.new) | DeepSeek-V3 |
| Researcher | Web search, summarisation, fact-finding | TBD — see §5.5 | — |
| Developer | Code generation via opencode in sandbox | opencode (model-agnostic) | configurable |
| Test Writer | Writes failing tests per spec | opencode (model-agnostic) | configurable |
| Reviewer | Code review, test validation, spec compliance | Qwen3-Coder (synthetic.new) | DeepSeek-R1 |
| Voice Interface | STT/TTS handling, wake word bridge | Local (Whisper + Porcupine) | API fallback |

Clawra and the Planner run directly through the Laravel AI SDK. The Developer, Test Writer, and Reviewer operate via opencode inside Docker Sandboxes. Additional agents (Browser, File Manager, DevOps) deferred to a later phase.

### 5.4 Developer Agent & opencode Integration

The Developer agent (and Test Writer) manage Docker Sandbox environments and drive opencode inside them, rather than calling an LLM API directly. This approach uses Claude Max and ChatGPT Plus through their official CLIs — the intended programmatic interfaces — while keeping the inference cost inside existing subscription budgets.

opencode runs as a server process inside the sandbox microVM. The Laravel app acts as a client to that server, passing structured task prompts and receiving results. This is preferable to treating opencode as a fire-and-forget CLI and parsing stdout — the client/server model provides a proper integration surface.

**opencode Desktop app monitoring:** During development of Clawra itself, it is valuable to observe opencode sessions in real time via the opencode Desktop app. Two integration paths need evaluation:

1. **Detect and connect** — Detect a running opencode Desktop app's server on the host, and connect the sandbox's opencode server to it. This lets the Desktop app observe sessions Clawra initiates.
2. **Manage and expose** — Clawra manages the opencode server inside the sandbox, and the opencode Desktop app is configured to connect to Clawra's managed server rather than spawning its own.

One of these approaches should be validated during Phase 1 sandbox work, as it will be practically useful immediately during Clawra's own development.

The developer agent is responsible for:
- Provisioning or resuming the correct Docker Sandbox for the project
- Injecting project context (relevant task description, workflow subtask instructions, test output where applicable)
- Driving opencode with structured prompts
- Streaming and capturing output
- Parsing output into a structured result (success, artifacts produced, test status, errors, continuation state)
- Triggering the next subtask agent on completion
- Persisting sandbox state between heartbeat cycles

### 5.5 Research Agent — Implementation TBD

The Research agent handles web search, summarisation, and fact-finding. Several implementation options need evaluation before committing to one:

- **synthetic.new `/search` endpoint** — Direct integration with the provider already in use; fewest moving parts
- **Firecrawl** — Purpose-built web scraping and research API with good AI assistant cookbook support; more capable for deep research tasks but an additional service dependency
- **Other options** — Tavily, Exa, Brave Search API, and similar research-focused APIs are also worth considering

This needs a brief evaluation spike before Phase 0 finalises the agent roster. The decision will affect what tools are exposed to the Research agent via the Laravel AI SDK.

### 5.6 Planning Workflow

Planning is a distinct activity separate from task execution. Before work begins on a new feature or bug fix, the user works with the Planner agent through the NativePHP UI to produce a project spec or implementation plan. This results in a structured plan that is stored against the project.

When tasks are derived from the plan, the relevant portions of the plan are attached to each task as context — the developer agent does not re-read the entire project plan, only the parts germane to the specific subtasks it is executing.

**Automated planning from coding agents:** Most supported coding CLI tools (including opencode) can generate their own internal implementation plan before beginning work. Rather than always prompting the user to review these, the coordinator can intercept the plan output and automatically review it — approving it or flagging concerns — so the user is only pulled in when something needs genuine human judgment. This path should be explored as an alternative to always surfacing planning decisions to the UI.

### 5.7 Workflow-Driven Task Execution

Tasks are not simply delegated to an agent with a prompt to "follow a workflow." Instead, each task is configured with a specific **workflow** — a defined sequence of subtasks, each assigned to an agent, each with its own inputs and expected outputs. Workflows are first-class entities in the data model.

**Workflow mechanics:**
- A workflow defines an ordered sequence of subtask types (e.g. test-write → implement → review)
- Each subtask specifies which agent handles it, what inputs it receives, and what a successful output looks like
- On subtask completion, the result (including any errors, test output, or review feedback) is passed to the next subtask as structured context
- If a subtask fails — for example, the test suite returns errors after implementation — the active agent passes the relevant failure information back to the previous agent for resolution before proceeding. This feedback loop can iterate up to a configurable maximum.

**Sandboxes and worktrees across subtasks:** A key design question for Phase 1 is whether subtasks within a single workflow share one persistent sandbox or each get their own. Sharing a sandbox is simpler and keeps incremental work (installed packages, build artefacts) warm between subtasks. Using separate sandboxes or git worktrees per subtask allows easier rollback and parallel execution. The tradeoffs need hands-on evaluation before this is finalised.

**Initial TDD workflow** (assumes Laravel + Pest v4):

1. **Test-write subtask** — Test Writer agent writes failing Pest tests against the spec and acceptance criteria
2. **Implement subtask** — Developer agent implements until Pest unit and feature tests pass
3. **Review subtask** — Reviewer agent inspects the diff and test results against the original spec; runs the full test suite including any configured end-to-end tests; either approves, requests changes (returning to the implement subtask), or escalates to the user

Projects without an existing test suite receive a lighter default: the Test Writer only writes tests for new code, building coverage incrementally.

### 5.8 Codebase Indexing

A robust codebase indexing and context engine is important for giving agents structural awareness of each project without overloading context windows. No current tooling has been evaluated that cleanly supports PHP and Laravel to the required level. This is a research task before Phase 2 — the right option needs to be identified before committing to an integration.

### 5.9 Docker Sandbox Environments

All coding agent subtasks run inside Docker Sandboxes — Docker Desktop for Windows' microVM-based isolated environments. Each sandbox gets its own private Docker daemon, so opencode can build images, run containers, and execute tests inside the sandbox without any access to the host Docker environment.

Key characteristics:

- **Persistence** — Sandboxes persist until explicitly removed. A project's sandbox survives heartbeat cycles, keeping installed packages and Docker image cache warm between tasks.
- **Bidirectional file sync** — The project workspace syncs at the same absolute path between host and sandbox.
- **Credential injection** — API keys and configuration must be present before the Docker Desktop daemon starts. A custom sandbox template with credentials and opencode configuration pre-baked is the cleanest approach — defined once, reused across project sandboxes.
- **Network isolation** — Sandboxes cannot communicate with each other or with host localhost services. Outbound internet access is available and configurable.
- **CLI management** — Managed via `docker sandbox run`, `docker sandbox ls`, `docker sandbox rm`, `docker sandbox exec`. Clawra shells out to these via the Process facade.
- **Windows experimental status** — Docker Sandboxes on Windows are experimental as of Docker Desktop 4.58. macOS is the stable path. Worth monitoring release notes closely.

The initial design uses one persistent sandbox per project, but the sharing vs isolation tradeoff for workflow subtasks (see §5.7) may revise this.

### 5.10 Project State

Each project has a persistent state document (stored in the database) that contains:

- Project name, description, and goals
- Current status (active, paused, blocked, complete)
- A structured summary of what has been done
- The current working intent (what the system is trying to accomplish right now)
- Outstanding tasks with status, assigned workflow, and current subtask
- Test suite status and coverage snapshot (where available)
- A log of agent outputs, review decisions, and escalations
- Links to artifacts (files, repos, docs)

Clawra reads and updates this document. It is the ground truth that allows the system to resume work after failures, rate limit pauses, or restarts without re-reading everything from scratch.

---

## 6. Inference Awareness & Scheduling

### 6.1 Provider Registry Polling

A background job maintains current usage state for each configured provider. For providers with a usage API (OpenAI, Anthropic), this is a direct API call. For synthetic.new, usage is estimated from the system's own request logs matched against the known 5-hour rolling window and pack count. A simple dashboard view shows current provider status at a glance.

### 6.2 Task Cost Estimation

Before Clawra dispatches a task, it estimates inference cost using:

- Task type and priority classification
- Historical log data for similar tasks (requests made, approximate tokens, time to complete)
- Agent and provider preferences for that task type
- Current concurrency slots available per model

The estimate informs model selection and can gate or queue a task if no provider has sufficient remaining capacity. Tasks are never silently dropped — they enter a pending state with an estimated resume time based on the next window reset.

### 6.3 Heartbeat Scheduler

A Laravel scheduled job runs every 5 hours (aligned to the synthetic.new window reset). On each heartbeat:

1. Refresh all provider usage snapshots
2. Identify tasks that paused due to rate limits or sandbox failures
3. Re-queue eligible tasks in priority order
4. Run Clawra's background analysis pass: review all active projects, identify the next highest-priority action on each, and queue it if capacity exists
5. Log the heartbeat decision, including reasoning, for coordinator context on the next pass

The 5-hour interval is configurable. Providers with different window schedules can trigger independent partial heartbeats.

---

## 7. Voice Interface

### 7.1 Interaction Model

The user speaks a wake word, then a request. The system transcribes, routes to Clawra, executes, and responds via TTS. Clawra can ask clarifying questions back via TTS before dispatching tasks.

### 7.2 Components

- **Wake word detection** — Picovoice Porcupine (local, low-latency, Windows-compatible); custom wake word
- **Speech-to-text** — local Whisper (via whisper.cpp or faster-whisper); OpenAI Whisper API as fallback
- **Text-to-speech** — ElevenLabs via the Laravel AI SDK's TTS support; local Windows TTS as fallback
- **Voice bridge** — a lightweight local process (Node or Python) handling audio I/O and forwarding transcribed text to the Laravel app via a local HTTP endpoint or WebSocket

### 7.3 Platform

NativePHP wraps the full application as a desktop app with system tray integration and autostart. The voice bridge runs as a managed background process within the NativePHP app. Voice is not a prerequisite for core functionality — the NativePHP UI works independently of it.

---

## 8. Technical Stack

| Concern | Choice | Notes |
|---|---|---|
| Framework | Laravel 12 | Queues, scheduler, Eloquent, Process facade all relevant |
| Desktop runtime | NativePHP | Windows desktop app; system tray, autostart, voice bridge process |
| AI SDK | Laravel AI SDK (beta) | Coordinator, Planner, Researcher; not used for sandbox agents |
| Primary inference | synthetic.new | OpenAI-compatible and Anthropic-compatible endpoints |
| Coordinator fallback | Gemini API (free tier) | Zero-cost fallback when synthetic.new is rate-limited |
| Dev CLI | opencode | Runs inside Docker Sandbox; client/server integration model |
| Sandbox | Docker Sandboxes (Docker Desktop for Windows) | microVM-based; experimental on Windows |
| Code indexer | TBD — needs research | PHP/Laravel support required; current options unsatisfactory |
| Embeddings | nomic-ai/nomic-embed-text-v1.5 | Via synthetic.new; free, no rate limit impact — provisional until indexer chosen |
| Vector storage | PostgreSQL + pgvector | Provisional until indexer chosen |
| Voice (wake word) | Picovoice Porcupine | Local, Windows-compatible |
| Voice (STT) | Whisper (local) | Privacy-preserving; API fallback |
| Voice (TTS) | ElevenLabs + Windows TTS fallback | Via Laravel AI SDK |
| UI | Livewire / Filament | Project/task management, provider status, logs |
| Database | PostgreSQL (Windows native) | JSON columns for project state |
| Queue | Redis + Laravel Horizon | Agent job visibility and monitoring |

---

## 9. Data Model (High Level)

```
providers           — inference providers, models, rate limits, and current usage state
projects            — project metadata, goals, and state documents
tasks               — units of work with assigned workflow, status, and current subtask
subtasks            — individual steps within a task workflow, with agent, inputs, and outputs
workflows           — workflow definitions (ordered subtask types and routing rules)
task_logs           — granular output, request counts, and timing per subtask
review_logs         — Reviewer agent decisions, diffs reviewed, escalations
agents              — agent definitions (role, prompt, tools, model assignment)
sandboxes           — sandbox metadata and status per project
heartbeat_logs      — record of each scheduler pass, decisions made, tasks queued
```

---

## 10. Build Phases

### Phase 0 — Foundation
- NativePHP app scaffold with Laravel, Horizon, and scheduler
- Provider registry with synthetic.new + Gemini free tier configured
- Clawra coordinator agent via Laravel AI SDK (text input only)
- Evaluate and select Research agent implementation (synthetic /search, Firecrawl, or other)
- Planner agent via Laravel AI SDK
- Researcher agent wired up
- Basic project and task CRUD with state document model and workflow definitions
- Heartbeat scheduler with rate limit awareness
- Validate Laravel AI SDK fallback mechanism (synthetic.new → Gemini) under rate limit conditions
- Minimal Livewire UI

### Phase 1 — Developer Agent & Sandboxing
- Docker Sandbox manager (Process facade)
- Custom sandbox template with credentials and opencode config
- opencode client/server integration
- Evaluate opencode Desktop app monitoring integration (detect vs manage)
- Validate sandbox-per-project vs per-subtask approach
- Test Writer agent subtask
- Developer agent subtask
- Reviewer agent subtask
- Initial TDD workflow (Laravel + Pest v4)
- Subtask failure feedback loop (active agent → previous agent)
- Task cost estimation (basic, log-driven)
- Clawra routing logic with provider registry awareness

### Phase 2 — Planning & Codebase Indexing
- Structured planning flow in NativePHP UI (user + Planner)
- Plan-to-task context extraction (relevant plan portions attached to subtasks)
- Automated coordinator plan review (coordinator intercepts opencode plan output)
- Research and select codebase indexer with PHP/Laravel support
- Integrate chosen indexer; wire up as agent tool

### Phase 3 — Voice
- Whisper STT integration
- ElevenLabs TTS via SDK
- Wake word detection (Porcupine, Windows)
- Voice bridge process managed by NativePHP
- System tray and autostart polish

### Phase 4 — Self-improvement & Expansion
- System can delegate improvements to its own codebase to the developer agent
- Claude Code and Codex CLI as supplementary coding agents
- Additional synthetic.new model assignments as packs are added
- Additional workflow types beyond TDD

---

## 11. Key Risks & Mitigations

| Risk | Likelihood | Mitigation |
|---|---|---|
| opencode client/server API instability | High | Young tool; pin versions in sandbox template; abstract integration behind an interface |
| Laravel AI SDK fallback not sufficient for coordinator continuity | Medium | Validate early in Phase 0; implement explicit fallback logic in coordinator if needed |
| Agent doing redundant or contradictory work | High | Structured project state; Clawra reads it before every dispatch |
| Sandbox credential injection complexity | Medium | Custom sandbox template; defined once, documented clearly |
| Docker Sandboxes experimental on Windows | Medium | Monitor Docker Desktop release notes; macOS is the stable reference path |
| TDD enforcement impractical on projects without test infrastructure | Medium | Lighter default (new code only) for projects without test suites |
| Scope creep stalling Phase 0 | High | Phase 0 is deliberately minimal — Clawra + Planner + Researcher only, no sandbox, no voice |
| Codebase indexing gap | Medium | Not a Phase 0 or Phase 1 blocker; deferred to Phase 2 with a research task upfront |

---

## 12. Open Questions

1. **synthetic.new window reset behaviour** — Fixed UTC or rolling from each request? Affects heartbeat scheduler timing precision.
2. **Laravel AI SDK fallback adequacy** — Does the SDK's built-in model fallback handle the synthetic.new → Gemini transition cleanly, including mid-task failures? Or does Clawra need to manage this explicitly?
3. **Research agent implementation** — Evaluate synthetic.new `/search` endpoint vs Firecrawl vs other options before Phase 0 finalises.
4. **opencode Desktop app monitoring integration** — Detect-and-connect vs manage-and-expose? Needs a brief spike in Phase 1.
5. **Subtask sandbox sharing** — Should workflow subtasks share a single persistent sandbox per project or use separate sandboxes/worktrees? Tradeoffs between simplicity/warmth and isolation/rollback need hands-on evaluation.
6. **Codebase indexer** — No satisfactory PHP/Laravel-supporting option identified yet. Needs a research pass before Phase 2.
7. **opencode model switching** — How does opencode's on-the-fly model switching interact with the provider registry fallback config? Does it support the low-priority/fallback pattern cleanly?

---

## 13. Success Criteria (v1)

- Clawra accepts a text request and produces a task plan without manual intervention
- At least one project runs autonomously in the background across multiple heartbeat cycles with coherent state
- System correctly pauses and resumes tasks around synthetic.new rate limit windows without losing work; coordinator remains responsive via Gemini fallback during rate-limited periods
- Developer agent completes a non-trivial coding task via opencode in a Docker Sandbox, with Pest tests passing and Reviewer approval
- A failed subtask (e.g. tests failing post-implementation) correctly feeds back to the previous subtask agent and resolves without user intervention
- Clawra makes at least one observable model-selection decision based on live rate limit and concurrency data
