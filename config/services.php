<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'opencode' => [
        'binary' => env('OPENCODE_BINARY', 'opencode'),
        'timeout' => (int) env('OPENCODE_TIMEOUT', 120),
    ],

    'claude_code' => [
        'node_binary' => env('CLAUDE_CODE_NODE_BINARY', 'node'),
        'timeout' => (int) env('CLAUDE_CODE_TIMEOUT', 300),
    ],

    'docker_sandbox' => [
        'binary' => env('DOCKER_SANDBOX_BINARY', 'docker'),
        'image' => env('DOCKER_SANDBOX_IMAGE', 'clawra-sandbox:latest'),
        'timeout' => (int) env('DOCKER_SANDBOX_TIMEOUT', 600),
    ],

    'clawra' => [
        'agent_timeout' => (int) env('CLAWRA_AGENT_TIMEOUT', 300),
        'search_timeout' => (int) env('CLAWRA_SEARCH_TIMEOUT', 30),
    ],

];
