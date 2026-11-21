<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Linter\Tests\Stub\Invalid;

use DivisionByZeroError;
use PhPhD\ExceptionMapper\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Object\Try_;

use const PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Undefined\undefined_condition;

#[Try_]
final class UndefinedConstantConditionMessage
{
    #[Catch_(DivisionByZeroError::class, match: undefined_condition)]
    private ?int $divisor = null;
}
