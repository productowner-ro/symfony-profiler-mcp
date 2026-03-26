<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests\Profiler\Formatter;

use PHPUnit\Framework\TestCase;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\CollectorFormatterInterface;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\ExceptionCollectorFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\ExceptionDataCollector;

class ExceptionCollectorFormatterTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $formatter = new ExceptionCollectorFormatter();
        $this->assertInstanceOf(CollectorFormatterInterface::class, $formatter);
    }

    public function testGetNameReturnsException(): void
    {
        $formatter = new ExceptionCollectorFormatter();
        $this->assertSame('exception', $formatter->getName());
    }

    public function testFormatExtractsExceptionData(): void
    {
        $collector = $this->createCollectorWithException(
            new \RuntimeException('Something went wrong'),
        );

        $formatter = new ExceptionCollectorFormatter();
        $data = $formatter->format($collector);

        $this->assertArrayHasKey('exception', $data);
        $this->assertSame('RuntimeException', $data['exception']['class']);
        $this->assertSame('Something went wrong', $data['exception']['message']);
        $this->assertArrayHasKey('file', $data['exception']);
        $this->assertArrayHasKey('line', $data['exception']);
        $this->assertArrayHasKey('trace', $data['exception']);
        $this->assertIsArray($data['exception']['trace']);
    }

    public function testFormatReturnsFullTraceByDefault(): void
    {
        $collector = $this->createCollectorWithException(
            new \RuntimeException('test'),
        );

        $formatter = new ExceptionCollectorFormatter();
        $data = $formatter->format($collector);

        $this->assertGreaterThan(0, \count($data['exception']['trace']));
    }

    public function testFormatRespectsMaxTraceFrames(): void
    {
        $collector = $this->createCollectorWithException(
            new \RuntimeException('test'),
        );

        $formatter = new ExceptionCollectorFormatter(maxTraceFrames: 3);
        $data = $formatter->format($collector);

        $this->assertLessThanOrEqual(3, \count($data['exception']['trace']));
    }

    public function testFormatWithNoException(): void
    {
        $collector = new ExceptionDataCollector();
        $collector->collect(Request::create('/'), new Response());

        $formatter = new ExceptionCollectorFormatter();
        $data = $formatter->format($collector);

        $this->assertArrayHasKey('exception', $data);
        $this->assertNull($data['exception']);
    }

    public function testGetSummaryReturnsCompactData(): void
    {
        $collector = $this->createCollectorWithException(
            new \RuntimeException('Something went wrong'),
        );

        $formatter = new ExceptionCollectorFormatter();
        $summary = $formatter->getSummary($collector);

        $this->assertArrayHasKey('has_exception', $summary);
        $this->assertTrue($summary['has_exception']);
        $this->assertSame('RuntimeException', $summary['class']);
        $this->assertSame('Something went wrong', $summary['message']);
        $this->assertArrayNotHasKey('trace', $summary);
    }

    private function createCollectorWithException(\Throwable $exception): ExceptionDataCollector
    {
        $collector = new ExceptionDataCollector();
        $collector->collect(Request::create('/'), new Response(), $exception);

        return $collector;
    }
}
