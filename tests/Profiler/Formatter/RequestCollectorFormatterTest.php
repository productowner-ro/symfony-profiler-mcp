<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests\Profiler\Formatter;

use PHPUnit\Framework\TestCase;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\CollectorFormatterInterface;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\RequestCollectorFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;

class RequestCollectorFormatterTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $formatter = new RequestCollectorFormatter();
        $this->assertInstanceOf(CollectorFormatterInterface::class, $formatter);
    }

    public function testGetNameReturnsRequest(): void
    {
        $formatter = new RequestCollectorFormatter();
        $this->assertSame('request', $formatter->getName());
    }

    public function testFormatRedactsCookies(): void
    {
        $collector = $this->createCollector([
            'Cookie' => 'session_id=secret123; csrf=abc',
        ]);

        $formatter = new RequestCollectorFormatter();
        $data = $formatter->format($collector);

        $this->assertArrayHasKey('request_headers', $data);
        $cookies = $data['request_headers']['cookie'] ?? $data['request_headers']['Cookie'] ?? null;
        $this->assertSame('[REDACTED]', $cookies);
    }

    public function testFormatRedactsAuthorizationHeader(): void
    {
        $collector = $this->createCollector([
            'Authorization' => 'Bearer secret-token',
        ]);

        $formatter = new RequestCollectorFormatter();
        $data = $formatter->format($collector);

        $auth = $data['request_headers']['authorization'] ?? $data['request_headers']['Authorization'] ?? null;
        $this->assertSame('[REDACTED]', $auth);
    }

    public function testFormatIncludesBasicRequestData(): void
    {
        $collector = $this->createCollector();

        $formatter = new RequestCollectorFormatter();
        $data = $formatter->format($collector);

        $this->assertArrayHasKey('method', $data);
        $this->assertArrayHasKey('status_code', $data);
        $this->assertArrayHasKey('content_type', $data);
    }

    public function testGetSummaryReturnsCompactData(): void
    {
        $collector = $this->createCollector();

        $formatter = new RequestCollectorFormatter();
        $summary = $formatter->getSummary($collector);

        $this->assertArrayHasKey('method', $summary);
        $this->assertArrayHasKey('status_code', $summary);
        $this->assertArrayNotHasKey('request_headers', $summary);
    }

    public function testHeaderValuesAreStringsNotEmptyObjects(): void
    {
        $collector = $this->createCollector(['X-Custom' => 'my-value']);

        $formatter = new RequestCollectorFormatter();
        $data = $formatter->format($collector);

        $customHeader = $data['request_headers']['x-custom'] ?? null;
        $this->assertNotNull($customHeader);
        $this->assertNotSame([], $customHeader);
        // Must be a string or array of strings, not an empty object/Data
        $json = json_encode($data);
        $this->assertStringNotContainsString('{}', $json, 'Header values must not serialize as empty objects');
    }

    public function testSerializedCollectorHeadersAreExtracted(): void
    {
        $collector = $this->createCollector(['Accept' => 'application/json']);

        // Simulate profiler serialization round-trip
        $collector = unserialize(serialize($collector));

        $formatter = new RequestCollectorFormatter();
        $data = $formatter->format($collector);

        $accept = $data['request_headers']['accept'] ?? null;
        $this->assertNotNull($accept);
        $json = json_encode($data);
        $this->assertStringNotContainsString('{}', $json, 'Deserialized header values must not be empty objects');
    }

    public function testRequestAttributesAreExtracted(): void
    {
        $collector = $this->createCollector();

        $formatter = new RequestCollectorFormatter();
        $data = $formatter->format($collector);

        $json = json_encode($data['request_attributes']);
        $this->assertStringNotContainsString('{}', $json, 'Request attributes must not serialize as empty objects');
    }

    /**
     * @param array<string, string> $headers
     */
    private function createCollector(array $headers = []): RequestDataCollector
    {
        $collector = new RequestDataCollector();
        $request = Request::create('/test', 'GET', [], [], [], []);
        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }
        $response = new Response('OK', 200);
        $collector->collect($request, $response);
        $collector->lateCollect();

        return $collector;
    }
}
