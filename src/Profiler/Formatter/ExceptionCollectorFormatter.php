<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Profiler\Formatter;

use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\ExceptionDataCollector;

class ExceptionCollectorFormatter implements CollectorFormatterInterface
{
    /**
     * @param int $maxTraceFrames Maximum trace frames to include (0 = unlimited)
     */
    public function __construct(
        private readonly int $maxTraceFrames = 0,
    ) {
    }

    public function getName(): string
    {
        return 'exception';
    }

    /**
     * @return array<string, mixed>
     */
    public function format(DataCollectorInterface $collector): array
    {
        if (!$collector instanceof ExceptionDataCollector) {
            return [];
        }

        if (!$collector->hasException()) {
            return ['exception' => null];
        }

        $exception = $collector->getException();
        $trace = $collector->getTrace();

        if ($this->maxTraceFrames > 0) {
            $trace = \array_slice($trace, 0, $this->maxTraceFrames);
        }

        return [
            'exception' => [
                'class' => $exception->getClass(),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'status_code' => $exception->getStatusCode(),
                'trace' => $trace,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummary(DataCollectorInterface $collector): array
    {
        if (!$collector instanceof ExceptionDataCollector) {
            return [];
        }

        if (!$collector->hasException()) {
            return ['has_exception' => false];
        }

        $exception = $collector->getException();

        return [
            'has_exception' => true,
            'class' => $exception->getClass(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
    }
}
