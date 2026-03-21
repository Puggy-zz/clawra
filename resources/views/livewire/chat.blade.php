<div
    x-data="{
        scrollToBottom() {
            const el = this.$refs.messages;
            if (el) { el.scrollTop = el.scrollHeight; }
        }
    }"
    x-on:chat-message-sent.window="scrollToBottom()"
>
    {{-- Header: project + conversation selectors --}}
    <div class="panel-header" style="border-bottom:1px solid var(--border); flex-wrap:wrap; gap:10px;">
        <h2>Chat</h2>
        <div class="panel-header-actions" style="flex-wrap:wrap; gap:8px;">
            <select wire:model.live="activeProjectId" class="form-select">
                @foreach ($projects as $p)
                    <option value="{{ $p->id }}" @selected($activeProject->id === $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="activeConversationId" class="form-select">
                @foreach ($projectConversations as $conv)
                    <option value="{{ $conv->id }}" @selected($activeConversation->id === $conv->id)>{{ $conv->title }}</option>
                @endforeach
            </select>
            <button
                wire:click="newConversation"
                wire:loading.attr="disabled"
                wire:target="newConversation"
                class="btn btn-ghost btn-sm"
                title="New conversation"
            >+ New</button>
        </div>
    </div>

    {{-- Message thread --}}
    <div
        x-ref="messages"
        x-init="scrollToBottom()"
        @if ($isProcessing) wire:poll.2s @endif
        style="overflow-y:auto; max-height:420px; display:flex; flex-direction:column; gap:10px; padding:14px 20px;"
    >
        @foreach ($messages as $index => $msg)
            <div
                wire:key="msg-{{ $index }}"
                style="padding:10px 14px; border-radius:14px; max-width:88%;
                    {{ $msg['role'] === 'user'
                        ? 'align-self:flex-end; background:#efe4d8;'
                        : 'align-self:flex-start; background:var(--accent-soft);' }}"
            >
                <div style="font-size:0.77rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:4px; color:var(--muted);">
                    {{ $msg['role'] === 'user' ? 'You' : 'Coordinator' }}
                </div>
                <div style="line-height:1.45; font-size:0.88rem; white-space:pre-wrap;">{{ $msg['content'] }}</div>
            </div>
        @endforeach

        @if ($isProcessing)
            <div style="align-self:flex-start; padding:10px 14px; border-radius:14px; background:var(--accent-soft); color:var(--muted); font-size:0.82rem;">
                Thinking…
            </div>
        @endif

    </div>

    {{-- Error banner --}}
    @if ($errorMessage)
        <div style="border-top:1px solid var(--border); padding:8px 20px;">
            <span style="color:var(--danger); font-size:0.82rem;">{{ $errorMessage }}</span>
        </div>
    @endif

    {{-- Input --}}
    <div style="border-top:1px solid var(--border); padding:12px 20px; display:flex; gap:10px; align-items:flex-end;">
        <form wire:submit="sendMessage" style="display:flex; gap:10px; flex:1; align-items:flex-end;">
            <input
                wire:model="message"
                type="text"
                placeholder="Ask Clawra to plan, research, or track the next task…"
                class="form-input"
                style="flex:1;"
                wire:loading.attr="disabled"
                wire:target="sendMessage"
                @disabled($isProcessing)
                autocomplete="off"
            />
            <button
                type="submit"
                class="btn btn-primary btn-sm"
                wire:loading.attr="disabled"
                wire:target="sendMessage"
                @disabled($isProcessing)
            >
                <span wire:loading.remove wire:target="sendMessage">Send</span>
                <span wire:loading wire:target="sendMessage">…</span>
            </button>
        </form>
    </div>

    {{-- Status line --}}
    <div style="padding:4px 20px 10px; font-size:0.78rem; color:var(--muted);">
        @if ($isProcessing)
            <span style="color:var(--accent);">Processing…</span>
        @else
            <span wire:loading wire:target="sendMessage" style="color:var(--accent);">Queuing…</span>
            <span wire:loading.remove wire:target="sendMessage">
                Project: <strong>{{ $activeProject->name }}</strong>
            </span>
        @endif
    </div>
</div>
