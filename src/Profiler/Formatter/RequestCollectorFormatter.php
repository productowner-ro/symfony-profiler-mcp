<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Profiler\Formatter;

use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;

class RequestCollectorFormatter implements CollectorFormatterInterface
{
    private const REDACTED_HEADERS = [
        'cookie',
        'set-cookie',
        'authorization',
        'x-api-key',
        'x-csrf-token',
    ];

    public function getName(): string
    {
        return 'request';
    }

    /**
     * @return array<string, mixed>
     */
    public function format(DataCollectorInterface $collector): array
    {
        if (!$collector instanceof RequestDataCollector) {
            return [];
        }

        return [
            'method' => $collector->getMethod(),
            'content_type' => $collector->getContentType(),
            'status_code' => $collector->getStatusCode(),
            'status_text' => $collector->getStatusText(),
            'request_headers' => $this->redactHeaders($collector->getRequestHeaders()->all()),
            'response_headers' => $this->redactHeaders($collector->getResponseHeaders()->all()),
            'request_query' => $collector->getRequestQuery()->all(),
            'request_attributes' => $collector->getRequestAttributes()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummary(DataCollectorInterface $collector): array
    {
        if (!$collector instanceof RequestDataCollector) {
            return [];
        }

        return [
            'method' => $collector->getMethod(),
            'content_type' => $collector->getContentType(),
            'status_code' => $collector->getStatusCode(),
            'status_text' => $collector->getStatusText(),
        ];
    }

    /**
     * @param array<string, mixed> $headers
     *
     * @return array<string, mixed>
     */
    private function redactHeaders(array $headers): array
    {
        foreach ($headers as $name => $value) {
            if (\in_array(strtolower($name), self::REDACTED_HEADERS, true)) {
                $headers[$name] = '[REDACTED]';
            }
        }

        return $headers;
    }
}
