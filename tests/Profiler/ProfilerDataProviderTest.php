<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests\Profiler;

use PHPUnit\Framework\TestCase;
use ProductOwner\SymfonyProfilerMcp\Profiler\Model\ProfileSummary;
use ProductOwner\SymfonyProfilerMcp\Profiler\ProfilerDataProvider;
use ProductOwner\SymfonyProfilerMcp\Profiler\Storage\ProfilerStorageResolver;
use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class ProfilerDataProviderTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/profiler_data_test_'.uniqid();
        mkdir($this->tempDir.'/profiler', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testListProfilesReturnsSummaries(): void
    {
        $this->writeProfile('token1', 'GET', '/api/test', 200);

        $provider = $this->createProvider();
        $profiles = $provider->listProfiles();

        $this->assertNotEmpty($profiles);
        $this->assertContainsOnlyInstancesOf(ProfileSummary::class, $profiles);
        $this->assertSame('token1', $profiles[0]->token);
    }

    public function testFindProfileReturnsProfileByToken(): void
    {
        $this->writeProfile('token2', 'POST', '/api/submit', 201);

        $provider = $this->createProvider();
        $profile = $provider->findProfile('token2');

        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertSame('token2', $profile->getToken());
    }

    public function testFindProfileReturnsNullForUnknownToken(): void
    {
        $provider = $this->createProvider();

        $this->assertNull($provider->findProfile('nonexistent'));
    }

    public function testListAvailableCollectors(): void
    {
        $this->writeProfile('token3', 'GET', '/', 200);

        $provider = $this->createProvider();
        $collectors = $provider->listAvailableCollectors('token3');

        $this->assertIsArray($collectors);
    }

    private function writeProfile(string $token, string $method, string $url, int $statusCode): void
    {
        $storage = new FileProfilerStorage('file:'.$this->tempDir.'/profiler');
        $profiler = new Profiler($storage);
        $profile = new Profile($token);
        $profile->setMethod($method);
        $profile->setUrl($url);
        $profile->setStatusCode($statusCode);
        $profile->setIp('127.0.0.1');
        $profile->setTime(time());
        $profiler->saveProfile($profile);
    }

    private function createProvider(): ProfilerDataProvider
    {
        $resolver = $this->createMock(ProfilerStorageResolver::class);
        $resolver->method('resolve')->willReturn([
            [
                'storage' => new FileProfilerStorage('file:'.$this->tempDir.'/profiler'),
                'context' => null,
            ],
        ]);

        return new ProfilerDataProvider($resolver);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
