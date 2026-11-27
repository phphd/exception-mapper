<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Value\Blueprint;

use ArrayIterator;
use Iterator;
use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Matcher\ExceptionMatcher;
use PhPhD\ExceptionMapper\Object\Blueprint\Registry\ObjectExceptionMappingBlueprintRegistry;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Value\Iterable\Blueprint\CascadeIterableExceptionMatcherIteratorBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Value\Object\Blueprint\CascadeObjectExceptionMappingBlueprint;

/** @internal */
final class CascadeValueExceptionMatcherBlueprint
{
    private readonly CascadeObjectExceptionMappingBlueprint $cascadeObjectBlueprint;

    private readonly CascadeIterableExceptionMatcherIteratorBlueprint $cascadeIterableBlueprint;

    /** @param ObjectExceptionMappingBlueprintRegistry<object> $printRegistry */
    public function __construct(ObjectExceptionMappingBlueprintRegistry $printRegistry)
    {
        $this->cascadeObjectBlueprint = new CascadeObjectExceptionMappingBlueprint($printRegistry);
        $this->cascadeIterableBlueprint = new CascadeIterableExceptionMatcherIteratorBlueprint($this);
    }

    /** @return ?Iterator<int,ExceptionMatcher> */
    public function bind(ExceptionMappingNode $embracingNode): ?Iterator
    {
        if (null !== $objectNode = $this->cascadeObjectBlueprint->bind($embracingNode)) {
            return new ArrayIterator([$objectNode]);
        }

        if (null !== $itemsMatcherIterator = $this->cascadeIterableBlueprint->bind($embracingNode)) {
            return $itemsMatcherIterator->getIterator();
        }

        return null;
    }
}
