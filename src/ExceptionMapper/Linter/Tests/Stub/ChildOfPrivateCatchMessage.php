<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Linter\Tests\Stub;

use DivisionByZeroError;
use PhPhD\ExceptionMapper\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Object\Try_;

#[Try_]
final class ChildOfPrivateCatchMessage extends ParentPrivateCatchMessage
{
    #[Catch_(DivisionByZeroError::class)]
    private ?int $childDivisor = null;
}
