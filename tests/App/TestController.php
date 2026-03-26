<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests\App;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TestController
{
    public function ok(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }

    public function error(): Response
    {
        throw new \RuntimeException('Something went wrong in the application');
    }
}
