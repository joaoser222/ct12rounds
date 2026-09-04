<?php

declare(strict_types=1);

namespace App\Services\Mcp;

use App\Mcp\Servers\Ct12roundsServer;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Server\Tool;
use ReflectionClass;

class ChatToolSchemaProvider
{
    /**
     * Build OpenAI-compatible tool definitions from the read-only MCP resources
     * that the current user is allowed to access.
     *
     * @return array{tools: array<int, array>, map: array<string, array{resource: resource, params: array<int, string>}>}
     */
    public function readOnlyResourcesForCurrentUser(): array
    {
        $tools = [];
        $map = [];

        foreach ($this->resourceClassList() as $class) {
            if (! class_exists($class)) {
                continue;
            }

            /** @var resource $resource */
            $resource = app($class);

            if (! $resource->eligibleForRegistration()) {
                continue;
            }

            $params = $this->uriVariableNames($resource->uri());
            $toolName = $this->toolName($resource->name());

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $toolName,
                    'description' => $resource->description(),
                    'parameters' => $this->buildParameters($params),
                ],
            ];

            $map[$toolName] = [
                'resource' => $resource,
                'params' => $params,
            ];
        }

        return ['tools' => $tools, 'map' => $map];
    }

    /**
     * Build OpenAI-compatible tool definitions from the writable MCP tools that
     * the current user is allowed to execute (gated by shouldRegister).
     *
     * @return array{tools: array<int, array>, map: array<string, array{tool: class-string<Tool>}>}
     */
    public function writableToolsForCurrentUser(): array
    {
        $tools = [];
        $map = [];

        foreach ($this->toolClassList() as $class) {
            if (! class_exists($class)) {
                continue;
            }

            /** @var Tool $tool */
            $tool = app($class);

            if (! $tool->eligibleForRegistration()) {
                continue;
            }

            $jsonSchema = new JsonSchemaTypeFactory;
            $objectType = $jsonSchema->object($tool->schema($jsonSchema));

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool->name(),
                    'description' => $tool->description(),
                    'parameters' => $objectType->toArray(),
                ],
            ];

            $map[$tool->name()] = ['tool' => $class];
        }

        return ['tools' => $tools, 'map' => $map];
    }

    /**
     * @return array<int, string>
     */
    private function resourceClassList(): array
    {
        $defaults = (new ReflectionClass(Ct12roundsServer::class))
            ->getDefaultProperties();

        return $defaults['resources'] ?? [];
    }

    /**
     * @return array<int, string>
     */
    private function toolClassList(): array
    {
        $defaults = (new ReflectionClass(Ct12roundsServer::class))
            ->getDefaultProperties();

        return $defaults['tools'] ?? [];
    }

    /**
     * @return array<int, string>
     */
    private function uriVariableNames(string $uri): array
    {
        preg_match_all('/{([^}]+)}/', $uri, $matches);

        return $matches[1] ?? [];
    }

    private function toolName(string $resourceName): string
    {
        $sanitized = (string) preg_replace('/[^a-zA-Z0-9_]/', '_', $resourceName);

        return 'read_'.$sanitized;
    }

    /**
     * @param  array<int, string>  $params
     * @return array<string, mixed>
     */
    private function buildParameters(array $params): array
    {
        $properties = [];

        foreach ($params as $param) {
            $properties[$param] = [
                'type' => 'string',
                'description' => 'Valor do parâmetro '.$param,
            ];
        }

        return [
            'type' => 'object',
            'properties' => (object) $properties,
            'required' => $params,
        ];
    }
}
