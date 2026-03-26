<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests\Profiler\Storage;

use PHPUnit\Framework\TestCase;
use ProductOwner\SymfonyProfilerMcp\Profiler\Storage\ProfilerStorageResolver;
use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;

class ProfilerStorageResolverTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/profiler_resolver_test_'.uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testResolvesDirectProfilerPath(): void
    {
        // Given a standard cache dir with a profiler subdirectory
        $cacheDir = $this->tempDir.'/var/cache/dev';
        mkdir($cacheDir.'/profiler', 0777, true);

        $resolver = new ProfilerStorageResolver($cacheDir, 'dev');
        $storages = $resolver->resolve();

        $this->assertNotEmpty($storages);
        $this->assertContainsOnlyInstancesOf(FileProfilerStorage::class, array_column($storages, 'storage'));
    }

    public function testResolvesMultiAppProfilerPaths(): void
    {
        // Given a multi-app structure: var/cache/app1_hash/dev/profiler, var/cache/app2_hash/dev/profiler
        $baseDir = $this->tempDir.'/var/cache';
        mkdir($baseDir.'/app1_hash/dev/profiler', 0777, true);
        mkdir($baseDir.'/app2_hash/dev/profiler', 0777, true);

        // The injected cache dir is one of the app dirs
        $cacheDir = $baseDir.'/app1_hash/dev';
        $resolver = new ProfilerStorageResolver($cacheDir, 'dev');
        $storages = $resolver->resolve();

        $this->assertCount(2, $storages);
        $contexts = array_column($storages, 'context');
        $this->assertContains('app1', $contexts);
        $this->assertContains('app2', $contexts);
    }

    public function testReturnsEmptyWhenNoProfilerDirsExist(): void
    {
        $cacheDir = $this->tempDir.'/var/cache/dev';
        mkdir($cacheDir, 0777, true);

        $resolver = new ProfilerStorageResolver($cacheDir, 'dev');
        $storages = $resolver->resolve();

        $this->assertEmpty($storages);
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
