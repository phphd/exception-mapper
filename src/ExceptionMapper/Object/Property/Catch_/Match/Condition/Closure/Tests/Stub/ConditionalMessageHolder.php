<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Closure\Tests\Stub;

use PhPhD\ExceptionMapper\Object\Try_;

#[Try_]
final class ConditionalMessageHolder
{
    public function __construct(
        public ConditionalMessage $conditionalMessage,
    ) {
    }
}
