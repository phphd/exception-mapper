<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Blueprint;

use AppendIterator;
use Iterator;
use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\ObjectExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\PropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\CascadePropertyExceptionMappingBlueprint;
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
        /** @var iterable<CascadePropertyExceptionMappingBlueprint> */
        public readonly iterable $cascadePropertyPrints,
    ) {
    }

    /** @param ?T $object */
    public function bind(?ExceptionMappingNode $embracingNode = null, ?object $object = null): ObjectExceptionMappingNode
    {
        Assert::notNull($object);
        Assert::isInstanceOf($object, $this->className); // @phpstan-ignore staticMethod.alreadyNarrowedType

        /** @var AppendIterator<int,ExceptionMappingNode,Iterator<int,ExceptionMappingNode>> $catchProperties */
        $catchProperties = new AppendIterator();

        /** @var AppendIterator<int,ExceptionMappingNode,Iterator<int,ExceptionMappingNode>> $cascadeProperties */
        $cascadeProperties = new AppendIterator();

        $objectNode = new ObjectExceptionMappingNode(
            $object,
            $embracingNode,
            new MemoizingIteratorAggregate($catchProperties),
            new MemoizingIteratorAggregate($cascadeProperties),
        );

        $catchProperties->append(self::bindProperties($this->propertyPrints, $objectNode));
        $cascadeProperties->append(self::bindProperties($this->cascadePropertyPrints, $objectNode));

        return $objectNode;
    }

    /**
     * @param iterable<PropertyExceptionMappingBlueprint|CascadePropertyExceptionMappingBlueprint> $prints
     *
     * @return Iterator<int,ExceptionMappingNode>
     */
    private static function bindProperties(iterable $prints, ObjectExceptionMappingNode $objectNode): Iterator
    {
        foreach ($prints as $print) {
            yield $print->bind($objectNode);
        }
    }
}
