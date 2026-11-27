<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Blueprint;

use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Blueprint\Matcher\LateBindingExceptionMatcherIteratorAggregate;
use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\ObjectExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\CatchExceptionMappingBlueprint;
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
        $propertyNode = new PropertyExceptionMappingNode(
            $embracingNode,
            $this->property->getName(),
            $this->getPropertyValue($embracingNode->getLeafObject()),
            $matchersIterable = new LateBindingExceptionMatcherIteratorAggregate($this->catchPrints),
        );

        $matchersIterable->attachNode($propertyNode);

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
