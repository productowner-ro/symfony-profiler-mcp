<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests\Resource;

use PHPUnit\Framework\TestCase;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\FormatterRegistry;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\GenericCollectorFormatter;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\RequestCollectorFormatter;
use ProductOwner\SymfonyProfilerMcp\Profiler\ProfilerDataProvider;
use ProductOwner\SymfonyProfilerMcp\Resource\ProfileResourceTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profile;

class ProfileResourceTemplateTest extends TestCase
{
    public function testGetProfileReturnsMetadataAndCollectorList(): void
    {
        $profile = $this->createProfile('abc123', ['request']);

        $provider = $this->createMock(ProfilerDataProvider::class);
        $provider->method('findProfile')->with('abc123')->willReturn($profile);
        $provider->method('listAvailableCollectors')->willReturn(['request']);

        $template = new ProfileResourceTemplate($provider, $this->createRegistry());
        $result = $template->getProfile('abc123');

        $this->assertArrayHasKey('uri', $result);
        $this->assertSame('symfony-profiler://profile/abc123', $result['uri']);
        $this->assertSame('application/json', $result['mimeType']);
        $decoded = json_decode($result['text'], true);
        $this->assertSame('abc123', $decoded['token']);
        $this->assertArrayHasKey('collectors', $decoded);
        $this->assertArrayHasKey('request', $decoded['collectors']);
        $this->assertSame('symfony-profiler://profile/abc123/request', $decoded['collectors']['request']['resource_uri']);
    }

    public function testGetCollectorReturnsFormattedData(): void
    {
        $collector = new RequestDataCollector();
        $request = Request::create('/test', 'GET');
        $collector->collect($request, new Response('OK', 200));
        $collector->lateCollect();

        $profile = new Profile('abc123');
        $profile->addCollector($collector);

        $provider = $this->createMock(ProfilerDataProvider::class);
        $provider->method('findProfile')->with('abc123')->willReturn($profile);

        $template = new ProfileResourceTemplate($provider, $this->createRegistry());
        $result = $template->getCollector('abc123', 'request');

        $this->assertSame('symfony-profiler://profile/abc123/request', $result['uri']);
        $decoded = json_decode($result['text'], true);
        $this->assertArrayHasKey('method', $decoded);
        $this->assertArrayHasKey('status_code', $decoded);
    }

    public function testGetCollectorReturnsErrorForUnknownToken(): void
    {
        $provider = $this->createMock(ProfilerDataProvider::class);
        $provider->method('findProfile')->willReturn(null);

        $template = new ProfileResourceTemplate($provider, $this->createRegistry());
        $result = $template->getCollector('nonexistent', 'request');

        $decoded = json_decode($result['text'], true);
        $this->assertArrayHasKey('error', $decoded);
    }

    public function testHasMcpResourceTemplateAttributes(): void
    {
        $ref = new \ReflectionClass(ProfileResourceTemplate::class);

        $profileMethod = $ref->getMethod('getProfile');
        $profileAttrs = $profileMethod->getAttributes(\Mcp\Capability\Attribute\McpResourceTemplate::class);
        $this->assertNotEmpty($profileAttrs);
        $this->assertSame('symfony-profiler://profile/{token}', $profileAttrs[0]->newInstance()->uriTemplate);

        $collectorMethod = $ref->getMethod('getCollector');
        $collectorAttrs = $collectorMethod->getAttributes(\Mcp\Capability\Attribute\McpResourceTemplate::class);
        $this->assertNotEmpty($collectorAttrs);
        $this->assertSame('symfony-profiler://profile/{token}/{collector}', $collectorAttrs[0]->newInstance()->uriTemplate);
    }

    /**
     * @param string[] $collectorNames
     */
    private function createProfile(string $token, array $collectorNames = []): Profile
    {
        $profile = new Profile($token);
        $profile->setMethod('GET');
        $profile->setUrl('/test');
        $profile->setStatusCode(200);
        $profile->setIp('127.0.0.1');
        $profile->setTime(1711000000);

        foreach ($collectorNames as $name) {
            if ('request' === $name) {
                $collector = new RequestDataCollector();
                $request = Request::create('/test', 'GET');
                $collector->collect($request, new Response('OK', 200));
                $collector->lateCollect();
                $profile->addCollector($collector);
            }
        }

        return $profile;
    }

    private function createRegistry(): FormatterRegistry
    {
        return new FormatterRegistry(
            [new RequestCollectorFormatter()],
            new GenericCollectorFormatter(),
        );
    }
}
