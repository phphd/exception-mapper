<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\FaultTolerant;

use PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

use function array_keys;

/** @internal */
final class FaultTolerantExceptionMapperDecorationCompilerPass implements CompilerPassInterface
{
    /**
     * After {@see ResolveInstanceofConditionalsPass} (100): autoconfigured mappers get the {@see FaultTolerantExceptionMapper} tag.
     * Before MonologBundle's LoggerChannelPass (0): the decorators get the channel logger.
     */
    public const PRIORITY = 50;

    public function process(ContainerBuilder $container): void
    {
        if (!self::isFaultTolerantMode($container)) {
            return;
        }

        foreach (array_keys($container->findTaggedServiceIds(FaultTolerantExceptionMapper::class)) as $mapperId) {
            self::decorateWithFaultTolerance($container, $mapperId);
        }
    }

    private static function isFaultTolerantMode(ContainerBuilder $container): bool
    {
        return $container->hasParameter('kernel.debug') && false === $container->getParameter('kernel.debug');
    }

    private static function decorateWithFaultTolerance(ContainerBuilder $container, string $mapperId): void
    {
        $container
            ->register($decoratorId = FaultTolerantExceptionMapper::class.'<'.$mapperId.'>', FaultTolerantExceptionMapper::class)
            ->setDecoratedService($mapperId)
            ->setArguments([
                new Reference($decoratorId.'.inner'),
                new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
            ->addTag('monolog.logger', [
                'channel' => PhdExceptionMapperExtension::LOGGER_CHANNEL,
            ])
        ;
    }
}
