<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Profiler\Storage;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;

class ProfilerStorageResolver
{
    public function __construct(
        private readonly string $cacheDir,
        private readonly string $environment,
    ) {
    }

    /**
     * @return array<array{storage: FileProfilerStorage, context: string|null}>
     */
    public function resolve(): array
    {
        $storages = [];
        $resolved = [];

        $directPath = $this->cacheDir.'/profiler';
        if (is_dir($directPath)) {
            $realDirectPath = realpath($directPath);
            $context = $this->extractAppId(dirname($this->cacheDir));
            $storages[] = [
                'storage' => new FileProfilerStorage('file:'.$realDirectPath),
                'context' => $context,
            ];
            $resolved[$realDirectPath] = true;
        }

        foreach ($this->discoverMultiAppPaths() as $path => $appId) {
            if (isset($resolved[$path])) {
                continue;
            }
            $storages[] = [
                'storage' => new FileProfilerStorage('file:'.$path),
                'context' => $appId,
            ];
            $resolved[$path] = true;
        }

        return $storages;
    }

    /**
     * @return array<string, string> map of profiler path => app id
     */
    private function discoverMultiAppPaths(): array
    {
        $parentDir = dirname($this->cacheDir);
        $baseSearchDir = $parentDir;

        if (str_contains(basename($parentDir), '_')) {
            $baseSearchDir = dirname($parentDir);
        }

        if (!is_dir($baseSearchDir)) {
            return [];
        }

        $paths = [];
        $finder = (new Finder())->directories()->in($baseSearchDir)->name('*_*')->depth(0);

        foreach ($finder as $dir) {
            $profilerPath = $dir->getRealPath().'/'.$this->environment.'/profiler';
            if (is_dir($profilerPath)) {
                $paths[$profilerPath] = $this->extractAppId($dir->getFilename());
            }
        }

        return $paths;
    }

    private function extractAppId(string $dirName): ?string
    {
        $basename = basename($dirName);

        if (str_contains($basename, '_')) {
            return explode('_', $basename, 2)[0];
        }

        return null;
    }
}
