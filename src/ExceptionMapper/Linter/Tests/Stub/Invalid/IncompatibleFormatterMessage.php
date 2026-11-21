<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Linter\Tests\Stub\Invalid;

use DivisionByZeroError;
use PhPhD\ExceptionMapper\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Object\Try_;

use const PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Symfony\Validator\Embedded\embedded_violations;

#[Try_]
final class IncompatibleFormatterMessage
{
    #[Catch_(DivisionByZeroError::class, format: embedded_violations)] // @phpstan-ignore argument.type (specifically test an incompatible formatter)
    private ?int $divisor = null;
}
