<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class ProbeOpenCodeModelCommand extends Command
{
    protected $signature = 'clawra:probe-model
                            {model : Full model string to test, e.g. synthetic/hf:THUDM/GLM-4.7}
                            {--prompt=Say "hello" in one word. : Prompt to send}
                            {--timeout=30 : Process timeout in seconds}';

    protected $description = 'Probe an opencode model string to verify it is accepted by the provider';

    public function handle(): int
    {
        $model = (string) $this->argument('model');
        $prompt = (string) $this->option('prompt');
        $timeout = (int) $this->option('timeout');
        $binary = (string) config('services.opencode.binary', 'opencode');
        $workspacePath = base_path();

        $command = sprintf(
            '%s run --format json --agent build --model %s %s',
            $binary,
            str_contains($model, ' ') ? '"'.str_replace('"', '\\"', $model).'"' : $model,
            '"'.str_replace('"', '\\"', $prompt).'"',
        );

        $this->line("Command: <info>{$command}</info>");
        $this->line('Running...');

        $result = Process::path($workspacePath)->timeout($timeout)->run($command);

        $raw = trim($result->output());
        $err = trim($result->errorOutput());

        $decoded = json_decode($raw, true);

        if (! $result->successful() || (is_array($decoded) && isset($decoded['error']))) {
            $this->error('FAIL');

            $errorMsg = is_array($decoded) && isset($decoded['error'])
                ? json_encode($decoded['error'])
                : ($err !== '' ? $err : $raw);

            $this->line("Error: <comment>{$errorMsg}</comment>");

            return Command::FAILURE;
        }

        $this->info('OK');

        $text = is_array($decoded)
            ? ($decoded['text'] ?? $decoded['output'] ?? $decoded['response'] ?? $raw)
            : $raw;

        $this->line('Response: '.str($text)->limit(200));

        return Command::SUCCESS;
    }
}
