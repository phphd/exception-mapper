<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified;

use LogicException;

use function sprintf;

/** @internal */
final class UnregisteredExceptionMatchFormatterException extends LogicException
{
    /** @param class-string $formatterId */
    public function __construct(string $formatterId)
    {
        parent::__construct(sprintf('Exception Match Formatter is not registered: "%s"', $formatterId));
    }
}
