<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Matcher;

use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\ExceptionReciprocal;

/** @internal */
interface ExceptionMatcher
{
    /** Returns TRUE if all exceptions were matched; FALSE otherwise */
    public function match(ExceptionReciprocal $reciprocal): bool;
}
