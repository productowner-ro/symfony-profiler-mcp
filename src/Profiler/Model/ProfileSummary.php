<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Profiler\Model;

readonly class ProfileSummary
{
    public function __construct(
        public string $token,
        public string $ip,
        public string $method,
        public string $url,
        public int $time,
        public int $statusCode,
        public ?string $context = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'token' => $this->token,
            'ip' => $this->ip,
            'method' => $this->method,
            'url' => $this->url,
            'time' => $this->time,
            'time_formatted' => date('Y-m-d H:i:s', $this->time),
            'status_code' => $this->statusCode,
            'resource_uri' => 'symfony-profiler://profile/'.$this->token,
        ];

        if (null !== $this->context) {
            $data['context'] = $this->context;
        }

        return $data;
    }
}
