<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Profiler\Formatter;

use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector;
use Symfony\Component\VarDumper\Cloner\Data;

class LoggerCollectorFormatter implements CollectorFormatterInterface
{
    public function getName(): string
    {
        return 'logger';
    }

    /**
     * @return array<string, mixed>
     */
    public function format(DataCollectorInterface $collector): array
    {
        if (!$collector instanceof LoggerDataCollector) {
            return [];
        }

        $summary = $this->buildSummary($collector);
        $logs = $this->extractLogs($collector);

        return [
            'summary' => $summary,
            'logs' => $logs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummary(DataCollectorInterface $collector): array
    {
        if (!$collector instanceof LoggerDataCollector) {
            return [];
        }

        return $this->buildSummary($collector);
    }

    /**
     * @return array<string, int>
     */
    private function buildSummary(LoggerDataCollector $collector): array
    {
        return [
            'error_count' => $collector->countErrors(),
            'deprecation_count' => $collector->countDeprecations(),
            'warning_count' => $collector->countWarnings(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractLogs(LoggerDataCollector $collector): array
    {
        $processedLogs = $collector->getProcessedLogs();
        $result = [];

        foreach ($processedLogs as $log) {
            $result[] = [
                'channel' => $log['channel'] ?? null,
                'level' => $log['priorityName'] ?? null,
                'priority' => $log['priority'] ?? null,
                'message' => $this->extractValue($log['message'] ?? ''),
                'context' => $this->extractValue($log['context'] ?? []),
                'timestamp' => $log['timestamp'] ?? null,
                'type' => $log['type'] ?? null,
            ];
        }

        return $result;
    }

    private function extractValue(mixed $value): mixed
    {
        if ($value instanceof Data) {
            return $value->getValue(true);
        }

        return $value;
    }
}
