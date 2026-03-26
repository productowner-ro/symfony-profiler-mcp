<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Profiler\Formatter;

use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class GenericCollectorFormatter implements CollectorFormatterInterface
{
    public function getName(): string
    {
        return '_generic';
    }

    /**
     * @return array<string, string>
     */
    public function format(DataCollectorInterface $collector): array
    {
        return [
            'collector' => $collector->getName(),
            'note' => 'No dedicated formatter available',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getSummary(DataCollectorInterface $collector): array
    {
        return [
            'collector' => $collector->getName(),
        ];
    }
}
