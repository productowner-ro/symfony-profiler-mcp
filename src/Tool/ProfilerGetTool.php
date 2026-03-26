<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use ProductOwner\SymfonyProfilerMcp\Profiler\ProfilerDataProvider;

#[McpTool(name: 'symfony-profiler-get', description: 'Get a specific Symfony profiler profile by token. Returns summary with collector URIs for drilling into specific collector data.')]
class ProfilerGetTool
{
    public function __construct(
        private readonly ProfilerDataProvider $provider,
    ) {
    }

    public function __invoke(string $token): string
    {
        $profile = $this->provider->findProfile($token);
        if (null === $profile) {
            return json_encode(['error' => 'Profile not found for token: '.$token], \JSON_THROW_ON_ERROR);
        }

        $collectors = $this->provider->listAvailableCollectors($token);
        $collectorUris = [];
        foreach ($collectors as $name) {
            $collectorUris[$name] = 'symfony-profiler://profile/'.$token.'/'.$name;
        }

        return json_encode([
            'token' => $profile->getToken(),
            'ip' => $profile->getIp(),
            'method' => $profile->getMethod(),
            'url' => $profile->getUrl(),
            'time' => $profile->getTime(),
            'time_formatted' => date('Y-m-d H:i:s', $profile->getTime()),
            'status_code' => $profile->getStatusCode(),
            'resource_uri' => 'symfony-profiler://profile/'.$token,
            'available_collectors' => $collectors,
            'collector_uris' => $collectorUris,
        ], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);
    }
}
