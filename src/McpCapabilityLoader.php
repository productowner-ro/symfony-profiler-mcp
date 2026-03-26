<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp;

use Mcp\Capability\Discovery\Discoverer;
use Mcp\Capability\Registry\Loader\LoaderInterface;
use Mcp\Capability\RegistryInterface;
use Psr\Log\LoggerInterface;

class McpCapabilityLoader implements LoaderInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function load(RegistryInterface $registry): void
    {
        $discoverer = new Discoverer($this->logger);
        $basePath = \dirname(__DIR__);
        $state = $discoverer->discover($basePath, ['src/Tool', 'src/Resource'], []);

        foreach ($state->getTools() as $ref) {
            $registry->registerTool($ref->tool, $ref->handler, isManual: true);
        }

        foreach ($state->getResourceTemplates() as $ref) {
            $registry->registerResourceTemplate($ref->resourceTemplate, $ref->handler, isManual: true);
        }
    }
}
