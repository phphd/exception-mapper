<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Tests\Stub\Message;

use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\ExceptionMatch;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use Throwable;

/** @implements ExceptionMatchFormatter<Throwable,string> */
final class ExceptionMessageFormatter implements ExceptionMatchFormatter
{
    public function format(ExceptionMatch $exceptionMatch): string
    {
        return $exceptionMatch->getException()->getMessage();
    }
}
