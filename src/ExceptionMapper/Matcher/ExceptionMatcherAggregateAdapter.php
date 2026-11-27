<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Matcher;

use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\ExceptionReciprocal;

/** @internal */
final class ExceptionMatcherAggregateAdapter implements ExceptionMatcher
{
    public function __construct(
        private readonly ExceptionMatcherIteratorAggregate $aggregate,
    ) {
    }

    public function match(ExceptionReciprocal $reciprocal): bool
    {
        foreach ($this->aggregate->getIterator() as $matcher) {
            if ($matcher->match($reciprocal)) {
                return true;
            }
        }

        return false;
    }
}
