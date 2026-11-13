<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry;

use LogicException;
use Symfony\Component\DependencyInjection\Definition;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function sprintf;

/** @internal */
final class ExceptionMatchFormatterServiceDefinition
{
    public function __construct(
        private readonly Definition $definition,
        private readonly string $serviceId,
        /** @var null|list<string> */
        private readonly ?array $aliasIds,
    ) {
    }

    public function autoconfigureQualifiedTags(): void
    {
        if (!$this->definition->isAutoconfigured()) {
            return;
        }

        foreach ($this->genericFormatterServiceClassNames() as $formatterClassName) {
            $this->autoconfigureQualifiedClassTag($formatterClassName);
        }
    }

    private function autoconfigureQualifiedClassTag(ExceptionMatchFormatterClassGenericName $formatterClassName): void
    {
        $this->retag(
            $formatterClassName::getBaseClassName(),
            $formatterClassName->getParameterTypesQualifiedClassName(),
        );
    }

    /** Used to build a registry's scoped tagged_locator() */
    public function materializeClassTags(): void
    {
        $formatterTagClassNames = $this->genericFormatterTagClassNames();

        if ([] === $formatterTagClassNames) {
            throw new LogicException(sprintf('Formatter service "%s" does not specify generic arguments. Define them in #[AsAlias(ExceptionMatchFormatter::class.\'<TException,TResult>\')], specifying your TException and TResult.', $this->serviceId));
        }

        foreach ($formatterTagClassNames as $formatterClassName) {
            $this->materializeClassTag($formatterClassName);
        }
    }

    private function materializeClassTag(ExceptionMatchFormatterClassGenericName $formatterClassName): void
    {
        // Registry scopes the services only by the result type:
        $this->retag(
            $formatterClassName->getParameterTypesQualifiedClassName(),
            $formatterClassName->getResultTypeQualifiedClassName(),
        );
    }

    /** @return list<ExceptionMatchFormatterClassGenericName> */
    private function genericFormatterTagClassNames(): array
    {
        return $this->genericFormatterClassNames(array_keys($this->definition->getTags()));
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
        // Must be kept on a separate line of code, lest PHP 8.1 fails with a segmentation fault:
        // https://github.com/php/php-src/issues/14003
        $genericNames = array_filter($names, ExceptionMatchFormatterClassGenericName::isArgumentQualifiedName(...));

        return array_values(array_map(
            static fn(string $className): ExceptionMatchFormatterClassGenericName => new ExceptionMatchFormatterClassGenericName($className),
            $genericNames,
        ));
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
