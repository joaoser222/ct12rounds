<?php

declare(strict_types=1);

return [
    'driver' => 'openai-compatible',

    'base_url' => env('MCP_CHAT_BASE_URL', 'https://integrate.api.nvidia.com/v1/chat/completions'),

    'api_key' => env('MCP_CHAT_API_KEY', ''),

    'temperature' => (float) env('MCP_CHAT_TEMPERATURE', 0.3),

    'max_tokens' => (int) env('MCP_CHAT_MAX_TOKENS', 1024),

    'max_tool_iterations' => (int) env('MCP_CHAT_MAX_TOOL_ITERATIONS', 5),

    'request_timeout' => (int) env('MCP_CHAT_REQUEST_TIMEOUT', 60),

    // Extra fields merged into the outgoing request body for every call. Used
    // here to disable the reasoning trace of Nemotron (enable_thinking=false),
    // since the chat expects plain content and tool_calls deltas.
    'chat_template_kwargs' => [
        'enable_thinking' => false,
    ],

    // Ordered list of models used as fallback chain. All entries share the
    // same base_url/api_key (e.g. Nvidia NIM integrate API). The first model
    // that answers successfully is kept for the whole conversation; on failure
    // the next model is tried.
    'providers' => [
        env('MCP_CHAT_MODEL', 'nvidia/nemotron-3-nano-30b-a3b'),
    ],
];
