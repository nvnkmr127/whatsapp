<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mcp\McpToolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * MCP (Model Context Protocol) — Streamable HTTP transport
 * Spec: https://modelcontextprotocol.io/specification/2025-03-26/basic/transports
 *
 * Auth: Bearer token via existing auth:sanctum + tenant middleware.
 * Each tenant's MCP endpoint is the same URL; the token determines the tenant.
 *
 * Implements three JSON-RPC 2.0 methods:
 *   initialize   → server capabilities
 *   tools/list   → tool manifest
 *   tools/call   → tool invocation
 */
class McpController extends Controller
{
    private const PROTOCOL_VERSION = '2024-11-05';
    private const SERVER_NAME      = 'WatxIO MCP Server';
    private const SERVER_VERSION   = '1.0.0';

    public function __construct(private McpToolRegistry $registry) {}

    public function handle(Request $request): JsonResponse
    {
        // Validate JSON-RPC 2.0 envelope
        if ($request->input('jsonrpc') !== '2.0') {
            return $this->error(null, -32600, 'Invalid Request: jsonrpc must be "2.0"');
        }

        $method = $request->input('method');
        $id     = $request->input('id');
        $params = $request->input('params', []);
        $team   = $request->user()->currentTeam;

        Log::debug('MCP request', ['method' => $method, 'team_id' => $team->id]);

        try {
            $result = match ($method) {
                'initialize'  => $this->initialize($params),
                'tools/list'  => $this->toolsList(),
                'tools/call'  => $this->toolsCall($params, $team),
                default       => throw new \InvalidArgumentException("Method not found: {$method}", -32601),
            };

            return response()->json(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);

        } catch (\InvalidArgumentException $e) {
            $code = $e->getCode() ?: -32601;
            return $this->error($id, $code, $e->getMessage());
        } catch (\Exception $e) {
            Log::warning('MCP tool error', ['method' => $method, 'error' => $e->getMessage(), 'team' => $team->id]);
            return $this->error($id, -32000, $e->getMessage());
        }
    }

    // ── Handlers ────────────────────────────────────────────────────────────

    private function initialize(array $params): array
    {
        return [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'serverInfo'      => [
                'name'    => self::SERVER_NAME,
                'version' => self::SERVER_VERSION,
            ],
            'capabilities' => [
                'tools' => ['listChanged' => false],
            ],
        ];
    }

    private function toolsList(): array
    {
        return ['tools' => $this->registry->manifest()];
    }

    private function toolsCall(array $params, $team): array
    {
        $name  = $params['name'] ?? null;
        $input = $params['arguments'] ?? [];

        if (! $name) {
            throw new \InvalidArgumentException('tools/call requires params.name');
        }

        // ponytail: resolve() throws InvalidArgumentException for unknown tool names
        $tool    = $this->registry->resolve($name);
        $content = $tool->handle($input, $team);

        return ['content' => $content, 'isError' => false];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function error(?int $id, int $code, string $message): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id'      => $id,
            'error'   => ['code' => $code, 'message' => $message],
        ]);
    }
}
