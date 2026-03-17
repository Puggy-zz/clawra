<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HeartbeatLog;
use App\Models\ReviewLog;
use App\Models\TaskLog;
use Illuminate\Database\Eloquent\Collection;

class LogService
{
    /**
     * Log a task event.
     */
    public function logTaskEvent(array $data): TaskLog
    {
        return TaskLog::create($data);
    }

    /**
     * Log a review event.
     */
    public function logReviewEvent(array $data): ReviewLog
    {
        return ReviewLog::create($data);
    }

    /**
     * Log a heartbeat event.
     */
    public function logHeartbeatEvent(array $data): HeartbeatLog
    {
        return HeartbeatLog::create($data);
    }

    /**
     * Get task logs by task ID.
     */
    public function getTaskLogsByTaskId(int $taskId): Collection
    {
        return TaskLog::where('task_id', $taskId)->get();
    }

    /**
     * Get review logs by task ID.
     */
    public function getReviewLogsByTaskId(int $taskId): Collection
    {
        return ReviewLog::where('task_id', $taskId)->get();
    }
}
