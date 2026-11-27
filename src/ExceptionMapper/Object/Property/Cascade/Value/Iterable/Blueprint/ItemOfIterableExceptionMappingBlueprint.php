<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Value\Iterable\Blueprint;

use AppendIterator;
use Iterator;
use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Matcher\ExceptionMatcher;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Value\Blueprint\CascadeValueExceptionMatcherBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Value\Iterable\ItemOfIterableExceptionMappingNode;

/**
 * @internal
 *
 * @implements ExceptionMappingBlueprint<ExceptionMappingNode,ItemOfIterableExceptionMappingNode>
 */
final class ItemOfIterableExceptionMappingBlueprint implements ExceptionMappingBlueprint
{
    public function __construct(
        private readonly CascadeValueExceptionMatcherBlueprint $cascadeValuePrint,
        private readonly int|string $key,
        private readonly mixed $item,
    ) {
    }

    public function bind(ExceptionMappingNode $embracingNode): ?ItemOfIterableExceptionMappingNode
    {
        /** @var AppendIterator<int,ExceptionMatcher,Iterator<int,ExceptionMatcher>> $matchers */
        $matchers = new AppendIterator();

        $itemNode = new ItemOfIterableExceptionMappingNode($embracingNode, $this->key, $this->item, $matchers);

        if (null === $cascadeMatchers = $this->cascadeValuePrint->bind($itemNode)) {
            return null;
        }

        $matchers->append($cascadeMatchers);

        return $itemNode;
    }
}
