<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry;

use Throwable;

use function array_any;
use function array_map;

/** @internal */
final class ExceptionMatchFormatterClassGenericNameUnion
{
    /** @api */
    public function __construct(
        /** @var non-empty-list<ExceptionMatchFormatterClassGenericName> */
        private readonly array $classNames,
    ) {
    }

    /** @param class-string<Throwable> $exceptionClass */
    public function doesNotTakeException(string $exceptionClass): ?GenericParameterDoesNotTakeTypeException
    {
        if ($this->takesException($exceptionClass)) {
            return null;
        }

        return new GenericParameterDoesNotTakeTypeException($exceptionClass, $this->getExceptionTypes());
    }

    /** @param class-string<Throwable> $exceptionClass */
    private function takesException(string $exceptionClass): bool
    {
        return array_any(
            $this->classNames,
            static fn(ExceptionMatchFormatterClassGenericName $className): bool => $className->acceptsException($exceptionClass),
        );
    }

    /** @return list<class-string<Throwable>> */
    private function getExceptionTypes(): array
    {
        return array_map(
            static fn(ExceptionMatchFormatterClassGenericName $className): string => $className->getExceptionType(),
            $this->classNames,
        );
    }
}
