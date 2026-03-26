<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests\Profiler\Formatter;

use PHPUnit\Framework\TestCase;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\CollectorFormatterInterface;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\FormatterRegistry;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\GenericCollectorFormatter;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class FormatterRegistryTest extends TestCase
{
    public function testReturnsRegisteredFormatter(): void
    {
        $formatter = $this->createMock(CollectorFormatterInterface::class);
        $formatter->method('getName')->willReturn('request');

        $registry = new FormatterRegistry([$formatter], new GenericCollectorFormatter());

        $this->assertSame($formatter, $registry->get('request'));
    }

    public function testFallsBackToGenericFormatter(): void
    {
        $generic = new GenericCollectorFormatter();
        $registry = new FormatterRegistry([], $generic);

        $result = $registry->get('unknown_collector');

        $this->assertSame($generic, $result);
    }

    public function testFormatCollectorUsesCorrectFormatter(): void
    {
        $collector = $this->createMock(DataCollectorInterface::class);
        $collector->method('getName')->willReturn('request');

        $formatter = $this->createMock(CollectorFormatterInterface::class);
        $formatter->method('getName')->willReturn('request');
        $formatter->method('format')->with($collector)->willReturn(['method' => 'GET']);

        $generic = new GenericCollectorFormatter();
        $registry = new FormatterRegistry([$formatter], $generic);

        $this->assertSame(['method' => 'GET'], $registry->formatCollector($collector));
    }
}
