<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests\Profiler\Formatter;

use PHPUnit\Framework\TestCase;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\CollectorFormatterInterface;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\LoggerCollectorFormatter;
use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector;
use Symfony\Component\HttpKernel\Log\Logger;

class LoggerCollectorFormatterTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $formatter = new LoggerCollectorFormatter();
        $this->assertInstanceOf(CollectorFormatterInterface::class, $formatter);
    }

    public function testGetNameReturnsLogger(): void
    {
        $formatter = new LoggerCollectorFormatter();
        $this->assertSame('logger', $formatter->getName());
    }

    public function testFormatIncludesSummaryAndLogs(): void
    {
        $collector = $this->createCollectorWithLogs();

        $formatter = new LoggerCollectorFormatter();
        $data = $formatter->format($collector);

        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('logs', $data);
        $this->assertArrayHasKey('error_count', $data['summary']);
        $this->assertArrayHasKey('deprecation_count', $data['summary']);
        $this->assertArrayHasKey('warning_count', $data['summary']);
    }

    public function testGetSummaryReturnsCountsOnly(): void
    {
        $collector = $this->createCollectorWithLogs();

        $formatter = new LoggerCollectorFormatter();
        $summary = $formatter->getSummary($collector);

        $this->assertArrayHasKey('error_count', $summary);
        $this->assertArrayNotHasKey('logs', $summary);
    }

    public function testFormatWithEmptyLogs(): void
    {
        $collector = new LoggerDataCollector(null, null, null);
        $collector->lateCollect();

        $formatter = new LoggerCollectorFormatter();
        $data = $formatter->format($collector);

        $this->assertSame([], $data['logs']);
        $this->assertSame(0, $data['summary']['error_count']);
    }

    private function createCollectorWithLogs(): LoggerDataCollector
    {
        $logger = new Logger(output: fopen('/dev/null', 'w'));
        $logger->info('Matched route', ['route' => 'test_ok']);
        $logger->error('Something failed', ['exception' => 'RuntimeException']);

        $collector = new LoggerDataCollector($logger, null, null);
        $collector->lateCollect();

        return $collector;
    }
}
