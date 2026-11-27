<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint;

use ArrayIterator;
use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Matcher\ExceptionMatcher;
use PhPhD\ExceptionMapper\Matcher\ExceptionMatcherAggregateAdapter;
use PhPhD\ExceptionMapper\Matcher\ExceptionMatcherIteratorAggregate;
use PhPhD\ExceptionMapper\Object\Blueprint\Registry\ObjectExceptionMappingBlueprintRegistry;
use PhPhD\ExceptionMapper\Object\ObjectExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\Property\Iterable\IterablePropertyExceptionMatcherIteratorAggregate;
use PhPhD\ExceptionMapper\Object\Property\PropertyExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\Property\Type\Specification\MixedIterableType;
use PhPhD\ExceptionMapper\Object\Property\Type\Specification\MixedObjectType;
use ReflectionProperty;

/**
 * @internal
 *
 * @implements ExceptionMappingBlueprint<ObjectExceptionMappingNode,PropertyExceptionMappingNode>
 */
final class CascadePropertyExceptionMappingBlueprint implements ExceptionMappingBlueprint
{
    public function __construct(
        public readonly ReflectionProperty $property,
        /** @var ObjectExceptionMappingBlueprintRegistry<object> */
        private readonly ObjectExceptionMappingBlueprintRegistry $printRegistry,
    ) {
    }

    /** @param ObjectExceptionMappingNode $embracingNode */
    public function bind(ExceptionMappingNode $embracingNode): PropertyExceptionMappingNode
    {
        $name = $this->property->getName();
        $value = $this->getPropertyValue($embracingNode->getLeafObject());

        /** @var ArrayIterator<int,ExceptionMatcher> $matchers */
        $matchers = new ArrayIterator();

        $propertyNode = new PropertyExceptionMappingNode($embracingNode, $name, $value, $matchers);

        if (null !== $innerObjectMatcher = $this->innerObjectMatcher($propertyNode)) {
            $matchers->append($innerObjectMatcher);
        } elseif (null !== $innerObjectsIterableMatcher = $this->innerObjectsIterableMatcher($propertyNode)) {
            $matchers->append(new ExceptionMatcherAggregateAdapter($innerObjectsIterableMatcher));
        }

        return $propertyNode;
    }

    private function getPropertyValue(object $object): mixed
    {
        if (!$this->property->isInitialized($object)) {
            return null;
        }

        return $this->property->getValue($object);
    }

    private function innerObjectMatcher(PropertyExceptionMappingNode $property): ?ExceptionMatcher
    {
        if (!self::isObject()($object = $property->getValue())) {
            return null;
        }

        $innerObjectPrint = $this->printRegistry->getMappingBlueprint($object::class);

        /** @noinspection PhpNullSafeOperatorCanBeUsedInspection */
        if (null === $innerObjectPrint) {
            return null;
        }

        return $innerObjectPrint->bind($property, $object);
    }

    public static function isObject(): MixedObjectType
    {
        static $isObject = new MixedObjectType();

        /** @phpstan-var MixedObjectType */
        return $isObject;
    }

    private function innerObjectsIterableMatcher(PropertyExceptionMappingNode $property): ?ExceptionMatcherIteratorAggregate
    {
        $value = $property->getValue();

        if ([] === $value || !self::isIterable()($value)) {
            return null;
        }

        return new IterablePropertyExceptionMatcherIteratorAggregate($property, $this->printRegistry);
    }

    /** Iterable might contain #[Try_] objects */
    public static function isIterable(): MixedIterableType
    {
        static $isIterable = new MixedIterableType();

        /** @phpstan-var MixedIterableType */
        return $isIterable;
    }
}
