<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Connector\Symfony;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function str_starts_with;

/** @api */
final class PhdExceptionMapperBundle extends Bundle
{
    /** @override */
    public function build(ContainerBuilder $container): void
    {
        foreach (self::filterPasses(PhdExceptionMapperExtension::beforeOptimizationPasses()) as $pass => $priority) {
            $container->addCompilerPass($pass, PassConfig::TYPE_BEFORE_OPTIMIZATION, $priority);
        }

        foreach (self::filterPasses(PhdExceptionMapperExtension::optimizationPasses()) as $pass => $priority) {
            $container->addCompilerPass($pass, PassConfig::TYPE_OPTIMIZE, $priority);
        }
    }

    /** @override */
    protected function createContainerExtension(): PhdExceptionMapperExtension
    {
        return new PhdExceptionMapperExtension(generatedProxiesAllowed: true);
    }

    /**
     * @param iterable<CompilerPassInterface,int> $passes
     *
     * @return iterable<CompilerPassInterface,int>
     */
    private static function filterPasses(iterable $passes): iterable
    {
        foreach ($passes as $pass => $priority) {
            if (str_starts_with($pass::class, 'PhPhD\\')) {
                yield $pass => $priority;
            }
        }
    }
}
