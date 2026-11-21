<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Linter\Tests\Stub;

use DivisionByZeroError;
use PhPhD\ExceptionMapper\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Object\Try_;

#[Try_]
final class UnregisteredFormatterMessage
{
    #[Catch_(DivisionByZeroError::class, format: UnregisteredFormatter::class)]
    private ?int $divisor = null;
}
