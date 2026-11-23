<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\FaultTolerant\Tests\Stub;

use LogicException;
use PhPhD\ExceptionMapper\ExceptionMapper;
use Throwable;

/** @implements ExceptionMapper<never> */
final class FailingClientExceptionMapper implements ExceptionMapper
{
    public function map(Throwable $exception, object $object): never
    {
        throw new LogicException('The client mapping has failed.');
    }
}
