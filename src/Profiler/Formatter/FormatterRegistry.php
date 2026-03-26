<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Profiler\Formatter;

use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class FormatterRegistry
{
    /** @var array<string, CollectorFormatterInterface> */
    private array $formatters = [];

    /**
     * @param iterable<CollectorFormatterInterface> $formatters
     */
    public function __construct(
        iterable $formatters,
        private readonly GenericCollectorFormatter $fallback,
    ) {
        foreach ($formatters as $formatter) {
            $this->formatters[$formatter->getName()] = $formatter;
        }
    }

    public function get(string $collectorName): CollectorFormatterInterface
    {
        return $this->formatters[$collectorName] ?? $this->fallback;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatCollector(DataCollectorInterface $collector): array
    {
        return $this->get($collector->getName())->format($collector);
    }
}
