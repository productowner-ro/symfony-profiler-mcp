<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Profiler\Formatter;

use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\VarDumper\Cloner\Data;

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
            'request_headers' => $this->redactHeaders($this->extractValues($collector->getRequestHeaders()->all())),
            'response_headers' => $this->redactHeaders($this->extractValues($collector->getResponseHeaders()->all())),
            'request_query' => $this->extractValues($collector->getRequestQuery()->all()),
            'request_attributes' => $this->extractValues($collector->getRequestAttributes()->all()),
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

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function extractValues(array $values): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            $result[$key] = $this->extractValue($value);
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
