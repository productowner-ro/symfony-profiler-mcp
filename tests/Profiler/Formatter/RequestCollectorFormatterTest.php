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
