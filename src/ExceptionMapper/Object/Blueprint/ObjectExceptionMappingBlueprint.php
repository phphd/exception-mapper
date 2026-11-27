<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Blueprint;

use AppendIterator;
use Iterator;
use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\ObjectExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\PropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Util\Iterator\MemoizingIteratorAggregate;
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
        public readonly iterable $propertyPrints,
    ) {
    }

    /** @param ?T $object */
    public function bind(?ExceptionMappingNode $embracingNode = null, ?object $object = null): ObjectExceptionMappingNode
    {
        Assert::notNull($object);
        Assert::isInstanceOf($object, $this->className); // @phpstan-ignore staticMethod.alreadyNarrowedType

        /** @var AppendIterator<int,ExceptionMappingNode,Iterator<int,ExceptionMappingNode>> $properties */
        $properties = new AppendIterator();

        $objectNode = new ObjectExceptionMappingNode($object, $embracingNode, new MemoizingIteratorAggregate($properties));

        $properties->append($this->bindProperties($objectNode));

        return $objectNode;
    }

    /** @return Iterator<int,ExceptionMappingNode> */
    private function bindProperties(ObjectExceptionMappingNode $objectNode): Iterator
    {
        foreach ($this->propertyPrints as $propertyPrint) {
            yield $propertyPrint->bind($objectNode);
        }
    }
}
