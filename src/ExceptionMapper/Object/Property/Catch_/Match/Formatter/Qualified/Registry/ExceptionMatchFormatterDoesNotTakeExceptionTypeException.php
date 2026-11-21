<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry;

use LogicException;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use Throwable;

use function sprintf;

/** @internal */
final class ExceptionMatchFormatterDoesNotTakeExceptionTypeException extends LogicException
{
    public function __construct(
        /** @var class-string<ExceptionMatchFormatter<Throwable,mixed>> */
        public string $formatterId,
        GenericParameterDoesNotTakeTypeException $previous,
    ) {
        parent::__construct(
            sprintf('Exception Match Formatter "%s" does not take the exception type.', $this->formatterId),
            previous: $previous,
        );
    }
}
