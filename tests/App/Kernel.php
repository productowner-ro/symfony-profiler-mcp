<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp\Tests\App;

use ProductOwner\SymfonyProfilerMcp\SymfonyProfilerMcpBundle;
use Symfony\AI\McpBundle\McpBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new WebProfilerBundle();
        yield new McpBundle();
        yield new SymfonyProfilerMcpBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'profiler' => ['only_exceptions' => false, 'collect' => true],
            'router' => ['utf8' => true],
            'http_method_override' => false,
            'handle_all_throwables' => true,
        ]);

        $container->extension('web_profiler', [
            'toolbar' => false,
            'intercept_redirects' => false,
        ]);

        $container->extension('mcp', [
            'client_transports' => ['stdio' => true],
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('test_ok', '/test/ok')
            ->controller([TestController::class, 'ok']);
        $routes->add('test_error', '/test/error')
            ->controller([TestController::class, 'error']);
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/symfony-profiler-mcp/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/symfony-profiler-mcp/log';
    }
}
