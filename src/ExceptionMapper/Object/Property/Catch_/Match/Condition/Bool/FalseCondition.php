<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Bool;

use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\MatchCondition;
use Throwable;

/**
 * @api
 *
 * @template T of Throwable
 *
 * @implements MatchCondition<T>
 */
final class FalseCondition implements MatchCondition
{
    public function matches(Throwable $exception): bool
    {
        return false;
    }
}
