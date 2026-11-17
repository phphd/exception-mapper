<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\AutoTag;

use Generator;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterServiceDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Reference;

use function array_fill_keys;
use function array_keys;
use function array_map;
use function strval;

/** @internal */
final class ExceptionMatchFormatterTagAutoconfigurationCompilerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

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
        /** @var array<string,Reference> $formatterServiceReferences */
        $formatterServiceReferences = $this->findAndSortTaggedServices(ExceptionMatchFormatterServiceDefinition::iteratorTag(), $container);

        $aliasIdsByFormatterServiceId = $this->getAliasIdsByServiceIds($container, array_map(strval(...), $formatterServiceReferences));

        foreach ($formatterServiceReferences as $formatterId => $formatterServiceReference) {
            yield new ExceptionMatchFormatterServiceDefinition(
                $container->getDefinition($formatterServiceId = (string)$formatterServiceReference),
                $formatterId,
                $formatterServiceId,
                aliasIds: $aliasIdsByFormatterServiceId[$formatterServiceId],
            );
        }
    }

    /**
     * @param array<string> $formatterServiceIds
     *
     * @return array<string,list<string>> [service id => alias ids]
     */
    private function getAliasIdsByServiceIds(ContainerBuilder $container, array $formatterServiceIds): array
    {
        $aliasIdsByFormatterServiceId = array_fill_keys($formatterServiceIds, []);

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
