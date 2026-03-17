<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class UpdateFileTreeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clawra:update-file-tree';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the file tree documentation for AI agents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating file tree...');

        // Get all custom files (excluding vendor, node_modules, etc.)
        $files = $this->getCustomFiles();

        // Generate markdown content
        $content = "# Project File Tree\n\n";
        $content .= "This file contains the current file structure of the project.\n\n";
        $content .= "```\n";
        $content .= $this->formatTree($files);
        $content .= "\n```";

        // Write to file
        $path = base_path('.ai/guidelines/file-tree.md');
        file_put_contents($path, $content);

        $this->info('File tree updated successfully!');

        // Run boost:install to update guidelines
        $this->info('Running boost:install...');
        exec('php artisan boost:install --guidelines --skills --mcp --no-interaction --silent', $output, $returnCode);

        if ($returnCode === 0) {
            $this->info('Boost guidelines updated successfully!');
        } else {
            $this->error('Failed to update boost guidelines.');
        }

        return Command::SUCCESS;
    }

    /**
     * Get all custom files in the project
     */
    private function getCustomFiles(): array
    {
        $excludeDirs = [
            'vendor',
            'node_modules',
            'storage',
            'bootstrap/cache',
            '.git',
        ];

        $excludeFiles = [
            'test-file.txt', // Exclude our test file
        ];

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path(), RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            $relativePath = substr($file->getPathname(), strlen(base_path()) + 1);

            // Skip excluded directories
            $skip = false;
            foreach ($excludeDirs as $excludeDir) {
                if (str_starts_with($relativePath, $excludeDir)) {
                    $skip = true;
                    break;
                }
            }

            // Skip excluded files
            foreach ($excludeFiles as $excludeFile) {
                if ($relativePath === $excludeFile) {
                    $skip = true;
                    break;
                }
            }

            if (! $skip) {
                $files[] = $relativePath;
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Format files as a tree structure
     */
    private function formatTree(array $files): string
    {
        $output = '';
        foreach ($files as $file) {
            $output .= $file."\n";
        }

        return $output;
    }
}
