<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Matcher;

use Iterator;
use IteratorAggregate;

/**
 * @internal
 *
 * @extends IteratorAggregate<int,ExceptionMatcher>
 */
interface ExceptionMatcherIteratorAggregate extends IteratorAggregate
{
    /** @return Iterator<int,ExceptionMatcher> */
    public function getIterator(): Iterator;
}
