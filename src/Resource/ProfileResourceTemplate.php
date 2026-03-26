<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Resource;

use Mcp\Capability\Attribute\McpResourceTemplate;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\FormatterRegistry;
use ProductOwner\SymfonyProfilerMcp\Profiler\ProfilerDataProvider;

class ProfileResourceTemplate
{
    public function __construct(
        private readonly ProfilerDataProvider $provider,
        private readonly FormatterRegistry $formatterRegistry,
    ) {
    }

    /**
     * @return array{uri: string, mimeType: string, text: string}
     */
    #[McpResourceTemplate(
        uriTemplate: 'symfony-profiler://profile/{token}',
        name: 'symfony-profile-overview',
        description: 'Full profile metadata with list of available collectors and their resource URIs.',
        mimeType: 'application/json',
    )]
    public function getProfile(string $token): array
    {
        $profile = $this->provider->findProfile($token);
        if (null === $profile) {
            return $this->errorResponse(
                'symfony-profiler://profile/'.$token,
                'Profile not found for token: '.$token,
            );
        }

        $collectors = [];
        foreach ($this->provider->listAvailableCollectors($token) as $name) {
            $collectors[$name] = [
                'resource_uri' => 'symfony-profiler://profile/'.$token.'/'.$name,
            ];
        }

        return [
            'uri' => 'symfony-profiler://profile/'.$token,
            'mimeType' => 'application/json',
            'text' => json_encode([
                'token' => $profile->getToken(),
                'ip' => $profile->getIp(),
                'method' => $profile->getMethod(),
                'url' => $profile->getUrl(),
                'time' => $profile->getTime(),
                'time_formatted' => date('Y-m-d H:i:s', $profile->getTime()),
                'status_code' => $profile->getStatusCode(),
                'collectors' => $collectors,
            ], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * @return array{uri: string, mimeType: string, text: string}
     */
    #[McpResourceTemplate(
        uriTemplate: 'symfony-profiler://profile/{token}/{collector}',
        name: 'symfony-collector-data',
        description: 'Formatted collector-specific data for a given profile token.',
        mimeType: 'application/json',
    )]
    public function getCollector(string $token, string $collector): array
    {
        $uri = 'symfony-profiler://profile/'.$token.'/'.$collector;

        $profile = $this->provider->findProfile($token);
        if (null === $profile) {
            return $this->errorResponse($uri, 'Profile not found for token: '.$token);
        }

        if (!$profile->hasCollector($collector)) {
            return $this->errorResponse($uri, 'Collector "'.$collector.'" not found in profile '.$token);
        }

        $data = $this->formatterRegistry->formatCollector($profile->getCollector($collector));

        return [
            'uri' => $uri,
            'mimeType' => 'application/json',
            'text' => json_encode($data, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * @return array{uri: string, mimeType: string, text: string}
     */
    private function errorResponse(string $uri, string $message): array
    {
        return [
            'uri' => $uri,
            'mimeType' => 'application/json',
            'text' => json_encode(['error' => $message], \JSON_THROW_ON_ERROR),
        ];
    }
}
