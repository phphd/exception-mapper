<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Symfony\Validator\Embedded\Tests\Stub;

use PhPhD\ExceptionMapper\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Object\Try_;

use const PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Symfony\Validator\Embedded\embedded_violations;

#[Try_]
final class NestedMessageWithEmbeddedViolations
{
    #[Catch_(ViolationsEmbeddedExampleException::class, format: embedded_violations)]
    private int $violationListCapturedProperty;
}
