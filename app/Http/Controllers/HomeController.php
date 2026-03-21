<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Document;
use App\Models\Project;
use App\Models\Provider;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use App\Models\Task;
use App\Models\Workflow;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()
            ->withCount('tasks')
            ->with('conversations')
            ->orderByDesc('updated_at')
            ->get();

        $providers = Provider::query()
            ->with(['routes.models'])
            ->orderBy('name')
            ->get();

        $providerRoutes = ProviderRoute::query()
            ->with('provider')
            ->orderBy('name')
            ->get();

        $providerModels = ProviderModel::query()
            ->with('route.provider')
            ->orderBy('name')
            ->get();

        $agents = Agent::query()
            ->with(['runtimes.route', 'runtimes.model', 'runtimes.fallbackRoute', 'runtimes.fallbackModel'])
            ->orderBy('name')
            ->get();

        $workflows = Workflow::query()->orderBy('name')->get();

        $draftTasks = Task::query()
            ->where('status', 'draft')
            ->with('project')
            ->latest()
            ->get();

        $recentDocuments = Document::query()
            ->with('project')
            ->latest()
            ->limit(5)
            ->get();

        return view('home', compact('projects', 'providers', 'providerRoutes', 'providerModels', 'agents', 'workflows', 'draftTasks', 'recentDocuments'));
    }
}
