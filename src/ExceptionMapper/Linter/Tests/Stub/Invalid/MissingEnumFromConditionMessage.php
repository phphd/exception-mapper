<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Linter\Tests\Stub\Invalid;

use PhPhD\ExceptionMapper\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Object\Try_;
use ValueError;

use const PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\PHP\Enum\enum_value;

#[Try_]
final class MissingEnumFromConditionMessage
{
    #[Catch_(ValueError::class, match: enum_value)]
    public mixed $deliveryMethod;
}
