<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry;

use LogicException;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function class_exists;
use function sprintf;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

class_exists(ContainerConfigurator::class);

/** @internal */
final class ExceptionMatchFormatterServiceDefinition
{
    /** @var ?list<ExceptionMatchFormatterClassGenericName> */
    private ?array $genericFormatterTagClassNames = null;

    public function __construct(
        private readonly Definition $definition,
        private readonly string $formatterId,
        private readonly string $serviceId,
        /** @var null|list<string> */
        private readonly ?array $aliasIds,
    ) {
    }

    public static function iteratorTag(): TaggedIteratorArgument
    {
        return tagged_iterator(ExceptionMatchFormatter::class, 'id');
    }

    public function autoconfigureQualifiedTags(): void
    {
        if (!$this->definition->isAutoconfigured()) {
            return;
        }

        foreach ($this->genericFormatterServiceClassNames() as $formatterClassName) {
            $this->autoconfigureQualifiedGenericClassTag($formatterClassName);
        }

        $this->genericFormatterTagClassNames = null;
    }

    private function autoconfigureQualifiedGenericClassTag(ExceptionMatchFormatterClassGenericName $formatterClassName): void
    {
        $this->retag(
            $formatterClassName::getBaseClassName(),
            $formatterClassName->getArgumentTypesQualifiedClassName(),
        );
    }

    /** Used to build a registry's type-scoped tagged_locator() */
    public function materializeGenericClassTags(): void
    {
        $formatterTagClassNames = $this->genericFormatterTagClassNames();

        if ([] === $formatterTagClassNames) {
            throw new LogicException(sprintf('Formatter service "%s" does not specify generic arguments. Define them in #[AsAlias(ExceptionMatchFormatter::class.\'<TException,TResult>\')], specifying your TException and TResult.', $this->serviceId));
        }

        foreach ($formatterTagClassNames as $formatterClassName) {
            $this->materializeClassTag($formatterClassName);
        }
    }

    public function registerGenericClassNameUnionServiceDefinition(ContainerBuilder $container): void
    {
        $container->setDefinition($this->serviceId.'.formatterClassNameUnion', $this->genericFormatterClassNameUnionDefinition());
    }

    private function genericFormatterClassNameUnionDefinition(): Definition
    {
        return (new Definition(ExceptionMatchFormatterClassGenericNameUnion::class, [$this->genericFormatterClassNameDefinitions()]))
            ->addTag(ExceptionMatchFormatterClassGenericNameUnion::class, ['id' => $this->formatterId])
        ;
    }

    /** @return list<Definition> */
    private function genericFormatterClassNameDefinitions(): array
    {
        return array_map(self::formatterClassNameDefinition(...), $this->genericFormatterTagClassNames());
    }

    private static function formatterClassNameDefinition(ExceptionMatchFormatterClassGenericName $className): Definition
    {
        return new Definition($className::class, [$className->getExceptionType(), $className->getResultType()]);
    }

    private function materializeClassTag(ExceptionMatchFormatterClassGenericName $formatterClassName): void
    {
        // Registry scopes the services only by the result type:
        $this->retag(
            $formatterClassName->getArgumentTypesQualifiedClassName(),
            $formatterClassName->getResultTypeQualifiedClassName(),
        );
    }

    /** @return list<ExceptionMatchFormatterClassGenericName> */
    private function genericFormatterTagClassNames(): array
    {
        return $this->genericFormatterTagClassNames ??= $this->genericFormatterClassNames(array_keys($this->definition->getTags()));
    }

    /** @return list<ExceptionMatchFormatterClassGenericName> */
    private function genericFormatterServiceClassNames(): array
    {
        return $this->genericFormatterClassNames($this->serviceIds());
    }

    /** @return non-empty-list<string> */
    private function serviceIds(): array
    {
        Assert::notNull($this->aliasIds);

        return [$this->serviceId, ...$this->aliasIds];
    }

    /**
     * @param list<string> $names
     *
     * @return list<ExceptionMatchFormatterClassGenericName>
     */
    private function genericFormatterClassNames(array $names): array
    {
        // Must be kept on a separate line, lest PHP 8.1 fails with a segmentation fault:
        // https://github.com/php/php-src/issues/14003
        $formatterNames = array_filter($names, ExceptionMatchFormatterClassGenericName::isArgumentQualifiedClassName(...));

        return array_values(array_map(ExceptionMatchFormatterClassGenericName::fromArgumentQualifiedClassName(...), $formatterNames));
    }

    private function retag(string $tagName, string $asName): void
    {
        $this->tag($asName, $this->tagAttributes($tagName));
    }

    /** @return array<string,mixed> */
    private function tagAttributes(string $tag): array
    {
        /** @var array{array<string,mixed>} $tagAttributesList */
        $tagAttributesList = $this->definition->getTag($tag);

        Assert::count($tagAttributesList, 1);

        [$tagAttributes] = $tagAttributesList;

        return $tagAttributes;
    }

    /** @param array<string,mixed> $attributes */
    private function tag(string $tagName, array $attributes): void
    {
        if ($this->definition->hasTag($tagName)) {
            return;
        }

        $this->definition->addTag($tagName, $attributes);
    }
}
