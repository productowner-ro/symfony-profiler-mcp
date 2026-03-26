<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests\Integration;

use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\FormatterRegistry;
use ProductOwner\SymfonyProfilerMcp\Profiler\ProfilerDataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class ProfilerToolsTest extends WebTestCase
{
    /** @var callable|null */
    private static $previousExceptionHandler;

    protected function setUp(): void
    {
        self::$previousExceptionHandler = set_exception_handler(null);
        restore_exception_handler();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore the exception handler that was active before the test,
        // so PHPUnit doesn't flag us as risky for leaking Symfony's debug handler.
        restore_exception_handler();
    }

    public function testListProfilesAfterHttpRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/test/ok');

        $this->assertResponseIsSuccessful();

        $provider = static::getContainer()->get(ProfilerDataProvider::class);
        $profiles = $provider->listProfiles(limit: 5);

        $this->assertNotEmpty($profiles, 'Profiler should have captured the request');
        $this->assertSame(200, $profiles[0]->statusCode);
        $this->assertStringContainsString('symfony-profiler://profile/', $profiles[0]->toArray()['resource_uri']);
    }

    public function testFindProfileByToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/test/ok');

        $token = $client->getResponse()->headers->get('X-Debug-Token');
        $this->assertNotNull($token, 'Response should include X-Debug-Token header');

        $provider = static::getContainer()->get(ProfilerDataProvider::class);
        $profile = $provider->findProfile($token);

        $this->assertNotNull($profile);
        $this->assertSame($token, $profile->getToken());
    }

    public function testRequestFormatterReturnsRealHeaders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/test/ok');

        $token = $client->getResponse()->headers->get('X-Debug-Token');
        $profiler = static::getContainer()->get('profiler');
        \assert($profiler instanceof Profiler);
        $profile = $profiler->loadProfile($token);

        $registry = static::getContainer()->get(FormatterRegistry::class);
        $data = $registry->get('request')->format($profile->getCollector('request'));

        $this->assertSame('GET', $data['method']);
        $this->assertSame(200, $data['status_code']);

        $json = json_encode($data['request_headers']);
        $this->assertStringNotContainsString('{}', $json, 'Headers must contain real values, not empty objects');
    }

    public function testRequestFormatterRedactsCookies(): void
    {
        $client = static::createClient();
        $client->request('GET', '/test/ok', [], [], ['HTTP_COOKIE' => 'session=secret']);

        $token = $client->getResponse()->headers->get('X-Debug-Token');
        $profiler = static::getContainer()->get('profiler');
        \assert($profiler instanceof Profiler);
        $profile = $profiler->loadProfile($token);

        $registry = static::getContainer()->get(FormatterRegistry::class);
        $data = $registry->get('request')->format($profile->getCollector('request'));

        $cookie = $data['request_headers']['cookie'] ?? null;
        $this->assertSame('[REDACTED]', $cookie);
    }

    public function testExceptionFormatterExtractsErrorData(): void
    {
        $client = static::createClient();
        $client->catchExceptions(true);
        $client->request('GET', '/test/error');

        $this->assertResponseStatusCodeSame(500);

        $token = $client->getResponse()->headers->get('X-Debug-Token');
        $profiler = static::getContainer()->get('profiler');
        \assert($profiler instanceof Profiler);
        $profile = $profiler->loadProfile($token);

        $registry = static::getContainer()->get(FormatterRegistry::class);
        $data = $registry->get('exception')->format($profile->getCollector('exception'));

        $this->assertNotNull($data['exception']);
        $this->assertSame('RuntimeException', $data['exception']['class']);
        $this->assertSame('Something went wrong in the application', $data['exception']['message']);
        $this->assertArrayHasKey('trace', $data['exception']);
    }

    public function testLoggerFormatterReturnsStructuredData(): void
    {
        $client = static::createClient();
        $client->request('GET', '/test/ok');

        $token = $client->getResponse()->headers->get('X-Debug-Token');
        $profiler = static::getContainer()->get('profiler');
        \assert($profiler instanceof Profiler);
        $profile = $profiler->loadProfile($token);

        $registry = static::getContainer()->get(FormatterRegistry::class);
        $data = $registry->get('logger')->format($profile->getCollector('logger'));

        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('error_count', $data['summary']);
        $this->assertArrayHasKey('logs', $data);
        $this->assertIsArray($data['logs']);
        // Log entries require Monolog's debug logger — without it the array is empty.
        // The formatter still returns a valid structure either way.
    }
}
