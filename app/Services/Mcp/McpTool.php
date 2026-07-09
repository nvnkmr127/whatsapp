<?php

namespace App\Services\Mcp;

use App\Models\Team;

interface McpTool
{
    /**
     * JSON Schema descriptor returned in tools/list.
     * Must include: name, description, inputSchema (JSON Schema object).
     */
    public function schema(): array;

    /**
     * Execute the tool for the given team.
     * Return MCP content array: [['type' => 'text', 'text' => '...']]
     *
     * @throws \Exception on validation or business rule failure
     */
    public function handle(array $input, Team $team): array;
}
