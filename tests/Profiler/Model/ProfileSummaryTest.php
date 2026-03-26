<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests\Profiler\Model;

use PHPUnit\Framework\TestCase;
use ProductOwner\SymfonyProfilerMcp\Profiler\Model\ProfileSummary;

class ProfileSummaryTest extends TestCase
{
    public function testToArrayIncludesResourceUri(): void
    {
        $summary = new ProfileSummary(
            token: 'abc123',
            ip: '127.0.0.1',
            method: 'GET',
            url: '/api/test',
            time: 1711000000,
            statusCode: 200,
            context: null,
        );

        $data = $summary->toArray();

        $this->assertSame('abc123', $data['token']);
        $this->assertSame('GET', $data['method']);
        $this->assertSame('/api/test', $data['url']);
        $this->assertSame(200, $data['status_code']);
        $this->assertSame('symfony-profiler://profile/abc123', $data['resource_uri']);
        $this->assertArrayHasKey('time_formatted', $data);
    }

    public function testToArrayIncludesContextWhenPresent(): void
    {
        $summary = new ProfileSummary(
            token: 'abc123',
            ip: '127.0.0.1',
            method: 'POST',
            url: '/api/submit',
            time: 1711000000,
            statusCode: 201,
            context: 'frontend',
        );

        $data = $summary->toArray();

        $this->assertSame('frontend', $data['context']);
    }

    public function testToArrayExcludesContextWhenNull(): void
    {
        $summary = new ProfileSummary(
            token: 'abc123',
            ip: '127.0.0.1',
            method: 'GET',
            url: '/',
            time: 1711000000,
            statusCode: 200,
            context: null,
        );

        $data = $summary->toArray();

        $this->assertArrayNotHasKey('context', $data);
    }
}
