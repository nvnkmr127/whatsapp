<?php

namespace App\Livewire\Developer;

use App\Services\Mcp\McpToolRegistry;
use Livewire\Component;

class McpServerManager extends Component
{
    public $mcpConfigTemplate = '';

    public function mount()
    {
        $this->mcpConfigTemplate = json_encode([
            'mcpServers' => [
                'watxio' => [
                    'command' => 'npx',
                    'args' => [
                        '-y',
                        'mcp-remote',
                        url('/api/v1/mcp'),
                        '--header',
                        'Authorization: Bearer YOUR_API_TOKEN_HERE',
                    ],
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function render(McpToolRegistry $registry)
    {
        $tools = collect($registry->manifest())->sortBy('name')->values()->all();

        return view('livewire.developer.mcp-server-manager', [
            'tools' => $tools,
        ])->layout('layouts.app');
    }
}
