<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\Materialize;

use Generator;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\AutoTag\ExceptionMatchFormatterTagAutoconfigurationCompilerPass;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterServiceDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @internal */
final class ExceptionMatchFormatterTagMaterializationCompilerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * After {@see ExceptionMatchFormatterTagAutoconfigurationCompilerPass} and every before-optimization pass (default for passes).
     * Before {@see ServiceLocatorTagPass} (0): scoped tags must exist before tagged locators are built.
     */
    public const PRIORITY = 100;

    public function process(ContainerBuilder $container): void
    {
        foreach ($this->getFormatterServiceDefinitions($container) as $formatter) {
            $formatter->registerGenericClassNameUnionServiceDefinition($container);

            $formatter->materializeGenericClassTags();
        }
    }

    /** @return Generator<ExceptionMatchFormatterServiceDefinition> */
    private function getFormatterServiceDefinitions(ContainerBuilder $container): Generator
    {
        $formattersTag = ExceptionMatchFormatterServiceDefinition::iteratorTag();

        foreach ($this->findAndSortTaggedServices($formattersTag, $container) as $formatterId => $formatterServiceReference) {
            yield new ExceptionMatchFormatterServiceDefinition(
                $container->getDefinition($formatterServiceId = (string)$formatterServiceReference),
                (string)$formatterId,
                $formatterServiceId,
                aliasIds: null,
            );
        }
    }
}
