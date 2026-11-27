<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint;

use AppendIterator;
use Iterator;
use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Matcher\ExceptionMatcher;
use PhPhD\ExceptionMapper\Object\ObjectExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Value\Blueprint\CascadeValueExceptionMatcherBlueprint;
use PhPhD\ExceptionMapper\Object\Property\PropertyExceptionMappingNode;
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
        private readonly CascadeValueExceptionMatcherBlueprint $cascadeValuePrint,
    ) {
    }

    /** @param ObjectExceptionMappingNode $embracingNode */
    public function bind(ExceptionMappingNode $embracingNode): ?PropertyExceptionMappingNode
    {
        /** @var AppendIterator<int,ExceptionMatcher,Iterator<int,ExceptionMatcher>> $matchers */
        $matchers = new AppendIterator();

        $propertyNode = new PropertyExceptionMappingNode(
            $embracingNode,
            $this->property->getName(),
            $this->getPropertyValue($embracingNode->getLeafObject()),
            $matchers,
        );

        if (null === $cascadeMatchers = $this->cascadeValuePrint->bind($propertyNode)) {
            return null;
        }

        $matchers->append($cascadeMatchers);

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
