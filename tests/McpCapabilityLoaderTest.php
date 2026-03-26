<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests;

use Mcp\Capability\Registry\Loader\LoaderInterface;
use PHPUnit\Framework\TestCase;
use ProductOwner\SymfonyProfilerMcp\McpCapabilityLoader;

class McpCapabilityLoaderTest extends TestCase
{
    public function testImplementsLoaderInterface(): void
    {
        $loader = new McpCapabilityLoader(new \Psr\Log\NullLogger());
        $this->assertInstanceOf(LoaderInterface::class, $loader);
    }

    public function testLoadRegistersToolsAndResourceTemplates(): void
    {
        $loader = new McpCapabilityLoader(new \Psr\Log\NullLogger());

        $registry = $this->createMock(\Mcp\Capability\RegistryInterface::class);

        $registry->expects($this->exactly(2))
            ->method('registerTool')
            ->with(
                $this->isInstanceOf(\Mcp\Schema\Tool::class),
                $this->anything(),
                true,
            );

        $registry->expects($this->exactly(2))
            ->method('registerResourceTemplate')
            ->with(
                $this->isInstanceOf(\Mcp\Schema\ResourceTemplate::class),
                $this->anything(),
                $this->anything(),
                true,
            );

        $loader->load($registry);
    }

    public function testToolsAreRegisteredAsManual(): void
    {
        $loader = new McpCapabilityLoader(new \Psr\Log\NullLogger());

        $registry = $this->createMock(\Mcp\Capability\RegistryInterface::class);

        $registry->expects($this->exactly(2))
            ->method('registerTool')
            ->willReturnCallback(function ($tool, $handler, $isManual) {
                $this->assertTrue($isManual, 'Tools must be registered as manual to survive DiscoveryLoader clear()');
            });

        $registry->method('registerResourceTemplate');

        $loader->load($registry);
    }
}
