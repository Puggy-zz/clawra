<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\HeartbeatLog;
use Illuminate\View\View;
use Livewire\Component;

class RecentHeartbeats extends Component
{
    public function render(): View
    {
        $heartbeatLogs = HeartbeatLog::query()
            ->latest('timestamp')
            ->limit(3)
            ->get();

        return view('livewire.recent-heartbeats', compact('heartbeatLogs'));
    }
}
