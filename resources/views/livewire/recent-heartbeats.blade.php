<div wire:poll.5s class="panel-body" style="padding-top:10px; padding-bottom:10px;">
    <div class="heartbeat-list">
        @forelse ($heartbeatLogs as $log)
            @php
                $tasksQueued    = $log->tasks_queued ?? [];
                $decisions      = $log->decisions ?? [];
                $providerStatus = $log->provider_status ?? [];
                $hasDetail      = ! empty($decisions) || ! empty($tasksQueued) || ! empty($providerStatus);
            @endphp
            <div class="heartbeat-row" wire:key="hb-{{ $log->id }}"
                 x-data="{ open: false }"
                 @if($hasDetail) @click="open = !open" @endif>
                <div class="heartbeat-row-header">
                    <div>
                        <div class="heartbeat-detail">
                            @if (!empty($tasksQueued))
                                {{ count($tasksQueued) }} {{ \Illuminate\Support\Str::plural('task', count($tasksQueued)) }} dispatched
                            @elseif (!empty($decisions))
                                {{ $decisions[0]['message'] ?? (count($decisions).' '.\Illuminate\Support\Str::plural('decision', count($decisions))) }}
                            @else
                                Heartbeat ran
                            @endif
                        </div>
                        <div class="heartbeat-meta">
                            {{ $log->trigger ?? 'scheduled' }} · {{ $log->run_type ?? 'full' }}
                            @if($hasDetail) · <span style="color:var(--accent);">click to expand</span>@endif
                        </div>
                    </div>
                    <span class="heartbeat-time">
                        {{ $log->timestamp?->diffForHumans() }}
                    </span>
                </div>

                @if($hasDetail)
                <div class="heartbeat-detail-panel" x-show="open" x-cloak>
                    @if(!empty($decisions))
                    <div class="heartbeat-section">
                        <div class="heartbeat-section-title">Decisions</div>
                        @foreach($decisions as $decision)
                            <div class="heartbeat-decision">{{ $decision['message'] ?? '—' }}</div>
                        @endforeach
                    </div>
                    @endif

                    @if(!empty($tasksQueued))
                    <div class="heartbeat-section">
                        <div class="heartbeat-section-title">Tasks Dispatched</div>
                        @foreach($tasksQueued as $t)
                            <div class="heartbeat-task-item">
                                <span style="font-weight:600;">{{ $t['task_name'] ?? '—' }}</span>
                                @if(!empty($t['project']))
                                    <span style="color:var(--muted);">{{ $t['project'] }}</span>
                                @endif
                                @if(!empty($t['route']))
                                    <span class="badge badge-neutral">{{ $t['route'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @endif

                    @if(!empty($providerStatus))
                    <div class="heartbeat-section">
                        <div class="heartbeat-section-title">Provider Snapshot</div>
                        <div class="heartbeat-provider-grid">
                            @foreach($providerStatus as $ps)
                                <div class="heartbeat-provider-item">
                                    {{ $ps['route'] ?? $ps['provider'] ?? '—' }}
                                    <span class="badge badge-{{ $ps['status'] === 'active' ? 'active' : ($ps['status'] === 'rate-limited' ? 'paused' : 'neutral') }}" style="font-size:0.68rem; padding:1px 5px;">{{ $ps['status'] }}</span>
                                    @if(isset($ps['requests_remaining']) && $ps['requests_remaining'] !== null && $ps['requests_remaining'] < PHP_INT_MAX)
                                        <br><span style="font-size:0.72rem;">{{ $ps['requests_remaining'] }} rem</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        @empty
            <div class="empty" style="padding: 14px 0 6px;">No heartbeats yet.</div>
            <div class="heartbeat-note" style="text-align:center;">Heartbeats run every 3 hours</div>
        @endforelse
    </div>
</div>
