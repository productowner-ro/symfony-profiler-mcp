<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests\Profiler\Formatter;

use PHPUnit\Framework\TestCase;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\CollectorFormatterInterface;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\GenericCollectorFormatter;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class GenericCollectorFormatterTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $formatter = new GenericCollectorFormatter();
        $this->assertInstanceOf(CollectorFormatterInterface::class, $formatter);
    }

    public function testGetNameReturnsGeneric(): void
    {
        $formatter = new GenericCollectorFormatter();
        $this->assertSame('_generic', $formatter->getName());
    }

    public function testFormatReturnsCollectorNameOnly(): void
    {
        $collector = $this->createMock(DataCollectorInterface::class);
        $collector->method('getName')->willReturn('some_collector');

        $formatter = new GenericCollectorFormatter();
        $data = $formatter->format($collector);

        $this->assertSame('some_collector', $data['collector']);
        $this->assertSame('No dedicated formatter available', $data['note']);
    }

    public function testFormatNeverUsesVarDumper(): void
    {
        $collector = $this->createMock(DataCollectorInterface::class);
        $collector->method('getName')->willReturn('test');

        $formatter = new GenericCollectorFormatter();
        $data = $formatter->format($collector);

        $this->assertIsArray($data);
        foreach ($data as $value) {
            $this->assertIsString($value);
        }
    }
}
