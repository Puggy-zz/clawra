<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    if (class_exists('\Laravel\Ai\Ai')) {
        echo "Using Laravel Ai facade\n";
        // Create a simple agent
        $agent = new class implements Laravel\Ai\Contracts\Agent
        {
            use Laravel\Ai\Promptable;

            public function instructions(): Stringable|string
            {
                return 'You are a helpful AI assistant.';
            }
        };

        // Try to generate text
        $response = $agent->prompt('Hello, world!', provider: 'synthetic');
        echo 'Response: '.$response."\n";
    } else {
        echo "Laravel Ai facade not available\n";
    }
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    echo 'Trace: '.$e->getTraceAsString()."\n";
}
