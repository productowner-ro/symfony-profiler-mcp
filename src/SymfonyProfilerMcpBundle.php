<?php

declare(strict_types=1);

namespace ProductOwner\SymfonyProfilerMcp;

use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\CollectorFormatterInterface;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\FormatterRegistry;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\GenericCollectorFormatter;
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\RequestCollectorFormatter;
use ProductOwner\SymfonyProfilerMcp\Profiler\ProfilerDataProvider;
use ProductOwner\SymfonyProfilerMcp\Profiler\Storage\ProfilerStorageResolver;
use ProductOwner\SymfonyProfilerMcp\Resource\ProfileResourceTemplate;
use ProductOwner\SymfonyProfilerMcp\Tool\ProfilerGetTool;
use ProductOwner\SymfonyProfilerMcp\Tool\ProfilerListTool;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SymfonyProfilerMcpBundle extends AbstractBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->register(ProfilerStorageResolver::class)
            ->setArguments([
                '%kernel.cache_dir%',
                '%kernel.environment%',
            ]);

        $builder->register(GenericCollectorFormatter::class);

        $builder->register(RequestCollectorFormatter::class)
            ->addTag('profiler_mcp.formatter');

        $builder->registerForAutoconfiguration(CollectorFormatterInterface::class)
            ->addTag('profiler_mcp.formatter');

        $builder->register(FormatterRegistry::class)
            ->setArguments([
                new TaggedIteratorArgument('profiler_mcp.formatter'),
                new Reference(GenericCollectorFormatter::class),
            ]);

        $builder->register(ProfilerDataProvider::class)
            ->setArguments([
                new Reference(ProfilerStorageResolver::class),
            ]);

        $builder->register(ProfilerListTool::class)
            ->setArguments([new Reference(ProfilerDataProvider::class)])
            ->setAutoconfigured(true);

        $builder->register(ProfilerGetTool::class)
            ->setArguments([new Reference(ProfilerDataProvider::class)])
            ->setAutoconfigured(true);

        $builder->register(ProfileResourceTemplate::class)
            ->setArguments([
                new Reference(ProfilerDataProvider::class),
                new Reference(FormatterRegistry::class),
            ])
            ->setAutoconfigured(true);
    }
}
