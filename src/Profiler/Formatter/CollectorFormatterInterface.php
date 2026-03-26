<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Profiler\Formatter;

use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

interface CollectorFormatterInterface
{
    public function getName(): string;

    /**
     * @return array<string, mixed>
     */
    public function format(DataCollectorInterface $collector): array;

    /**
     * @return array<string, mixed>
     */
    public function getSummary(DataCollectorInterface $collector): array;
}
