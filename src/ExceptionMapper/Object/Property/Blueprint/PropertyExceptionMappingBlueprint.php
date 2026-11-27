<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Blueprint;

use ArrayIterator;
use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Matcher\ExceptionMatcher;
use PhPhD\ExceptionMapper\Matcher\ExceptionMatcherAggregateAdapter;
use PhPhD\ExceptionMapper\Object\ObjectExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\CatchExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Catch_\CatchAttributesExceptionMatcherAggregate;
use PhPhD\ExceptionMapper\Object\Property\PropertyExceptionMappingNode;
use ReflectionProperty;
use Throwable;

/**
 * @internal
 *
 * @implements ExceptionMappingBlueprint<ObjectExceptionMappingNode,PropertyExceptionMappingNode>
 */
final class PropertyExceptionMappingBlueprint implements ExceptionMappingBlueprint
{
    public function __construct(
        public readonly ReflectionProperty $property,
        /** @var iterable<CatchExceptionMappingBlueprint<Throwable>> */
        public readonly iterable $catchPrints,
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

        $matchers->append(new ExceptionMatcherAggregateAdapter(new CatchAttributesExceptionMatcherAggregate($propertyNode, $this->catchPrints)));

        return $propertyNode;
    }

    private function getPropertyValue(object $object): mixed
    {
        if (!$this->property->isInitialized($object)) {
            return null;
        }

        return $this->property->getValue($object);
    }
}
