<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Composite;

use Iterator;
use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Blueprint\MatchConditionBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\MatchCondition;
use PhPhD\ExceptionMapper\Util\Iterator\MemoizingIteratorAggregate;
use Throwable;

use function iterator_count;

/**
 * @internal
 *
 * @implements MatchConditionBlueprint<Throwable>
 */
final class CompositeMatchConditionBlueprint implements MatchConditionBlueprint
{
    public function __construct(
        /** @var iterable<MatchConditionBlueprint<Throwable>> */
        public readonly iterable $prints,
    ) {
    }

    public function bind(ExceptionMappingNode $node): CompositeMatchCondition
    {
        return new CompositeMatchCondition(new MemoizingIteratorAggregate($this->conditions($node)));
    }

    public function forceCompleteCompilation(): int
    {
        return iterator_count($this->getLeafPrintsDFS());
    }

    /** @return Iterator<MatchConditionBlueprint<Throwable>> */
    private function getLeafPrintsDFS(): Iterator
    {
        foreach ($this->prints as $print) {
            if ($print instanceof self) {
                yield from $print->getLeafPrintsDFS();
            } else {
                yield $print;
            }
        }
    }

    /** @return Iterator<MatchCondition<Throwable>> */
    private function conditions(ExceptionMappingNode $node): Iterator
    {
        foreach ($this->prints as $print) {
            yield $print->bind($node);
        }
    }
}
