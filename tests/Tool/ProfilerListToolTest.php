<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests\Tool;

use PHPUnit\Framework\TestCase;
use ProductOwner\SymfonyProfilerMcp\Profiler\Model\ProfileSummary;
use ProductOwner\SymfonyProfilerMcp\Profiler\ProfilerDataProvider;
use ProductOwner\SymfonyProfilerMcp\Tool\ProfilerListTool;

class ProfilerListToolTest extends TestCase
{
    public function testReturnsCompactSummariesWithResourceUri(): void
    {
        $provider = $this->createMock(ProfilerDataProvider::class);
        $provider->method('listProfiles')->willReturn([
            new ProfileSummary('token1', '127.0.0.1', 'GET', '/api/test', 1711000000, 200),
            new ProfileSummary('token2', '127.0.0.1', 'POST', '/api/submit', 1711000001, 201),
        ]);

        $tool = new ProfilerListTool($provider);
        $result = $tool(limit: 20);

        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertCount(2, $decoded['profiles']);
        $this->assertSame('symfony-profiler://profile/token1', $decoded['profiles'][0]['resource_uri']);
        $this->assertArrayNotHasKey('collectors', $decoded['profiles'][0]);
    }

    public function testPassesFilterParametersToProvider(): void
    {
        $provider = $this->createMock(ProfilerDataProvider::class);
        $provider->expects($this->once())
            ->method('listProfiles')
            ->with(10, null, null, 'POST', 500)
            ->willReturn([]);

        $tool = new ProfilerListTool($provider);
        $tool(limit: 10, method: 'POST', statusCode: 500);
    }

    public function testHasMcpToolAttribute(): void
    {
        $ref = new \ReflectionClass(ProfilerListTool::class);
        $attributes = $ref->getAttributes(\Mcp\Capability\Attribute\McpTool::class);

        $this->assertNotEmpty($attributes);
        $instance = $attributes[0]->newInstance();
        $this->assertSame('symfony-profiler-list', $instance->name);
    }
}
