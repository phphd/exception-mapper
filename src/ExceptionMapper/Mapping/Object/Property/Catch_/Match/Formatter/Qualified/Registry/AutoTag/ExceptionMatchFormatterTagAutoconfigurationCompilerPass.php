<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\AutoTag;

use Generator;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterServiceDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;

use function array_keys;

/** @internal */
final class ExceptionMatchFormatterTagAutoconfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * After {@see ResolveInstanceofConditionalsPass} (100): autoconfigured formatters get a base formatter class tag.
     * Before client's default passes (0): they see (and may adjust) the qualified class tags.
     */
    public const PRIORITY = 50;

    public function process(ContainerBuilder $container): void
    {
        foreach ($this->getFormatterServiceDefinitions($container) as $formatter) {
            $formatter->autoconfigureQualifiedTags();
        }
    }

    /** @return Generator<ExceptionMatchFormatterServiceDefinition> */
    private function getFormatterServiceDefinitions(ContainerBuilder $container): Generator
    {
        foreach ($this->getFormattersServicesAliasIds($container) as $formatterServiceId => $aliasIds) {
            yield new ExceptionMatchFormatterServiceDefinition($container->getDefinition($formatterServiceId), $formatterServiceId, aliasIds: $aliasIds);
        }
    }

    /** @return array<string,list<string>> [own id => alias ids] */
    private function getFormattersServicesAliasIds(ContainerBuilder $container): array
    {
        $aliasIdsByFormatterServiceId = [];

        foreach (array_keys($container->findTaggedServiceIds(ExceptionMatchFormatter::class)) as $formatterServiceId) {
            $aliasIdsByFormatterServiceId[$formatterServiceId] = [];
        }

        foreach (array_keys($container->getAliases()) as $aliasId) {
            $formatterServiceId = $this->aliasTarget($container, $aliasId);

            if (!isset($aliasIdsByFormatterServiceId[$formatterServiceId])) {
                continue;
            }

            $aliasIdsByFormatterServiceId[$formatterServiceId][] = $aliasId;
        }

        return $aliasIdsByFormatterServiceId;
    }

    private function aliasTarget(ContainerBuilder $container, string $aliasId): string
    {
        $aliasChain = [];
        $serviceId = $aliasId;

        while ($container->hasAlias($serviceId)) {
            if (isset($aliasChain[$serviceId])) {
                throw new ServiceCircularReferenceException($serviceId, [...array_keys($aliasChain), $serviceId]);
            }

            $aliasChain[$serviceId] = true;
            $serviceId = (string)$container->getAlias($serviceId);
        }

        return $serviceId;
    }
}
