<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Blueprint;

use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\MatchCondition;
use Throwable;

/**
 * @api
 *
 * @template T of Throwable
 */
interface MatchConditionBlueprint
{
    /** @return MatchCondition<T> */
    public function bind(ExceptionMappingNode $node): MatchCondition;
}
