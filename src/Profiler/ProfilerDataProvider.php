<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Profiler;

use ProductOwner\SymfonyProfilerMcp\Profiler\Model\ProfileSummary;
use ProductOwner\SymfonyProfilerMcp\Profiler\Storage\ProfilerStorageResolver;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class ProfilerDataProvider
{
    public function __construct(
        private readonly ProfilerStorageResolver $storageResolver,
    ) {
    }

    /**
     * @return ProfileSummary[]
     */
    public function listProfiles(
        int $limit = 20,
        ?string $ip = null,
        ?string $url = null,
        ?string $method = null,
        ?int $statusCode = null,
    ): array {
        $summaries = [];

        foreach ($this->storageResolver->resolve() as $entry) {
            $profiler = new Profiler($entry['storage']);
            $tokens = $profiler->find($ip, $url, $limit, $method, null, null, null !== $statusCode ? (string) $statusCode : null);

            foreach ($tokens as $token) {
                $profile = $profiler->loadProfile($token['token']);
                if ($profile instanceof Profile) {
                    $summaries[] = $this->profileToSummary($profile, $entry['context']);
                }
            }
        }

        usort($summaries, static fn (ProfileSummary $a, ProfileSummary $b) => $b->time <=> $a->time);

        return \array_slice($summaries, 0, $limit);
    }

    public function findProfile(string $token): ?Profile
    {
        foreach ($this->storageResolver->resolve() as $entry) {
            $profiler = new Profiler($entry['storage']);
            $profile = $profiler->loadProfile($token);
            if ($profile instanceof Profile) {
                return $profile;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function listAvailableCollectors(string $token): array
    {
        $profile = $this->findProfile($token);
        if (null === $profile) {
            return [];
        }

        return array_keys($profile->getCollectors());
    }

    private function profileToSummary(Profile $profile, ?string $context): ProfileSummary
    {
        return new ProfileSummary(
            token: $profile->getToken(),
            ip: $profile->getIp(),
            method: $profile->getMethod(),
            url: $profile->getUrl(),
            time: $profile->getTime(),
            statusCode: $profile->getStatusCode(),
            context: $context,
        );
    }
}
