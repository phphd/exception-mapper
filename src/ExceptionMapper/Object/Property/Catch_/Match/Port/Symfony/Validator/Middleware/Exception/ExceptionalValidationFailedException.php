<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\Symfony\Validator\Middleware\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;

/** @api */
interface ExceptionalValidationFailedException extends Throwable
{
    public function getViolatingMessage(): object;

    public function getViolations(): ConstraintViolationListInterface;
}
