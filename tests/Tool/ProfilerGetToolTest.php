<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests\Tool;

use PHPUnit\Framework\TestCase;
use ProductOwner\SymfonyProfilerMcp\Profiler\ProfilerDataProvider;
use ProductOwner\SymfonyProfilerMcp\Tool\ProfilerGetTool;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profile;

class ProfilerGetToolTest extends TestCase
{
    public function testReturnsSummaryNotAllCollectors(): void
    {
        $profile = new Profile('abc123');
        $profile->setMethod('GET');
        $profile->setUrl('/test');
        $profile->setStatusCode(200);
        $profile->setIp('127.0.0.1');
        $profile->setTime(1711000000);
        $profile->addCollector(new RequestDataCollector());

        $provider = $this->createMock(ProfilerDataProvider::class);
        $provider->method('findProfile')->with('abc123')->willReturn($profile);
        $provider->method('listAvailableCollectors')->willReturn(['request']);

        $tool = new ProfilerGetTool($provider);
        $result = $tool(token: 'abc123');

        $decoded = json_decode($result, true);
        $this->assertSame('abc123', $decoded['token']);
        $this->assertSame('symfony-profiler://profile/abc123', $decoded['resource_uri']);
        $this->assertArrayHasKey('available_collectors', $decoded);
        $this->assertContains('request', $decoded['available_collectors']);
        // Must NOT contain raw collector data — that's what the resource is for
        $this->assertArrayNotHasKey('collectors', $decoded);
    }

    public function testReturnsErrorForUnknownToken(): void
    {
        $provider = $this->createMock(ProfilerDataProvider::class);
        $provider->method('findProfile')->willReturn(null);

        $tool = new ProfilerGetTool($provider);
        $result = $tool(token: 'nonexistent');

        $decoded = json_decode($result, true);
        $this->assertArrayHasKey('error', $decoded);
    }

    public function testHasMcpToolAttribute(): void
    {
        $ref = new \ReflectionClass(ProfilerGetTool::class);
        $attributes = $ref->getAttributes(\Mcp\Capability\Attribute\McpTool::class);

        $this->assertNotEmpty($attributes);
        $instance = $attributes[0]->newInstance();
        $this->assertSame('symfony-profiler-get', $instance->name);
    }

    public function testCollectorUrisPointToResourceTemplates(): void
    {
        $profile = new Profile('xyz789');
        $profile->setMethod('POST');
        $profile->setUrl('/submit');
        $profile->setStatusCode(201);
        $profile->setIp('127.0.0.1');
        $profile->setTime(1711000000);

        $provider = $this->createMock(ProfilerDataProvider::class);
        $provider->method('findProfile')->willReturn($profile);
        $provider->method('listAvailableCollectors')->willReturn(['request', 'exception']);

        $tool = new ProfilerGetTool($provider);
        $result = $tool(token: 'xyz789');

        $decoded = json_decode($result, true);
        $this->assertArrayHasKey('collector_uris', $decoded);
        $this->assertSame('symfony-profiler://profile/xyz789/request', $decoded['collector_uris']['request']);
        $this->assertSame('symfony-profiler://profile/xyz789/exception', $decoded['collector_uris']['exception']);
    }
}
