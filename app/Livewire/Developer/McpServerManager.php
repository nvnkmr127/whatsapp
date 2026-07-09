<?php

namespace App\Livewire\Developer;

use Livewire\Component;
use App\Services\Mcp\McpToolRegistry;
use App\Models\ApiToken;
use Illuminate\Support\Str;

class McpServerManager extends Component
{
    public $apiUrl;
    public $activeTokenName = 'Claude Desktop MCP';
    public $mcpConfigTemplate = '';
    
    public function mount()
    {
        $this->apiUrl = url('/api/v1/mcp');
        $this->generateConfigTemplate();
    }

    public function generateConfigTemplate()
    {
        // We look for a token that might be used for MCP, or just use a placeholder
        $token = auth()->user()->currentTeam->apiTokens()->where('name', 'Claude Desktop MCP')->first();
        $tokenPlaceholder = $token ? 'YOUR_API_TOKEN_HERE (See API Tokens tab)' : 'YOUR_API_TOKEN_HERE';

        $config = [
            "mcpServers" => [
                "watxio" => [
                    "command" => "curl",
                    "args" => [
                        "-s",
                        "-X", "POST",
                        $this->apiUrl,
                        "-H", "Authorization: Bearer {$tokenPlaceholder}",
                        "-H", "Content-Type: application/json",
                        "-d", "@-"
                    ]
                ]
            ]
        ];

        $this->mcpConfigTemplate = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function createDedicatedToken()
    {
        // Redirect to API tokens page with a pre-filled name if possible, or just redirect
        return redirect()->route('developer.api-tokens');
    }

    public function render(McpToolRegistry $registry)
    {
        $manifest = $registry->manifest();
        
        // Group tools by some basic logic for the UI if desired, or just pass them
        $tools = collect($manifest)->sortBy('name')->values()->all();

        return view('livewire.developer.mcp-server-manager', [
            'tools' => $tools
        ])->layout('layouts.app');
    }
}
