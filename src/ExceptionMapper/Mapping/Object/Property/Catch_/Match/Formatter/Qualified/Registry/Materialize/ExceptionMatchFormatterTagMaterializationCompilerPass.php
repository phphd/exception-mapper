<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\Materialize;

use Generator;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\AutoTag\ExceptionMatchFormatterTagAutoconfigurationCompilerPass;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterServiceDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function array_keys;

/** @internal */
final class ExceptionMatchFormatterTagMaterializationCompilerPass implements CompilerPassInterface
{
    /**
     * After {@see ExceptionMatchFormatterTagAutoconfigurationCompilerPass} and every before-optimization pass (default for passes).
     * Before {@see ServiceLocatorTagPass} (0): scoped tags must exist before tagged locators are built.
     */
    public const PRIORITY = 100;

    public function process(ContainerBuilder $container): void
    {
        foreach ($this->getFormatterDefinitions($container) as $formatter) {
            $formatter->materializeClassTags();
        }
    }

    /** @return Generator<ExceptionMatchFormatterServiceDefinition> */
    private function getFormatterDefinitions(ContainerBuilder $container): Generator
    {
        foreach (array_keys($container->findTaggedServiceIds(ExceptionMatchFormatter::class)) as $formatterServiceId) {
            yield new ExceptionMatchFormatterServiceDefinition($container->getDefinition($formatterServiceId), $formatterServiceId, aliasIds: null);
        }
    }
}
