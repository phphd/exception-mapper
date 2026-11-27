<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Blueprint;

use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Blueprint\Matcher\LateBindingExceptionMatcherIteratorAggregate;
use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\ObjectExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\PropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\CascadePropertyExceptionMappingBlueprint;
use Webmozart\Assert\Assert;

/**
 * @internal
 *
 * @template T of object
 *
 * @implements ExceptionMappingBlueprint<ExceptionMappingNode,ObjectExceptionMappingNode>
 */
final class ObjectExceptionMappingBlueprint implements ExceptionMappingBlueprint
{
    public function __construct(
        /** @var class-string<T> */
        public readonly string $className,
        /** @var iterable<PropertyExceptionMappingBlueprint> */
        public readonly iterable $catchPropertyPrints,
        /** @var iterable<CascadePropertyExceptionMappingBlueprint> */
        public readonly iterable $cascadePropertyPrints,
    ) {
    }

    /** @param ?T $object */
    public function bind(?ExceptionMappingNode $embracingNode = null, ?object $object = null): ObjectExceptionMappingNode
    {
        Assert::notNull($object);
        Assert::isInstanceOf($object, $this->className); // @phpstan-ignore staticMethod.alreadyNarrowedType

        $objectNode = new ObjectExceptionMappingNode(
            $object,
            $embracingNode,
            $catchProperties = new LateBindingExceptionMatcherIteratorAggregate($this->catchPropertyPrints),
            $cascadeProperties = new LateBindingExceptionMatcherIteratorAggregate($this->cascadePropertyPrints),
        );

        $catchProperties->attachNode($objectNode);
        $cascadeProperties->attachNode($objectNode);

        return $objectNode;
    }
}
