<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use ProductOwner\SymfonyProfilerMcp\Profiler\ProfilerDataProvider;

#[McpTool(name: 'symfony-profiler-list', description: 'List recent Symfony profiler profiles with summary data. Returns resource URIs for drilling into specific profiles.')]
class ProfilerListTool
{
    public function __construct(
        private readonly ProfilerDataProvider $provider,
    ) {
    }

    public function __invoke(
        int $limit = 20,
        ?string $method = null,
        ?string $url = null,
        ?string $ip = null,
        ?int $statusCode = null,
    ): string {
        $profiles = $this->provider->listProfiles($limit, $ip, $url, $method, $statusCode);

        return json_encode([
            'profiles' => array_map(
                static fn ($summary) => $summary->toArray(),
                $profiles,
            ),
        ], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);
    }
}
