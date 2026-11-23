<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\FaultTolerant;

use PhPhD\ExceptionMapper\ExceptionMapper;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Keeps a mapping failure from shadowing the original exception:
 * the failure is reported, and the caller can rethrow it.
 *
 * @internal
 *
 * @template T
 * @template-covariant TMapper of ExceptionMapper<T>
 *
 * @implements ExceptionMapper<T>
 */
final class FaultTolerantExceptionMapper implements ExceptionMapper
{
    public function __construct(
        /** @var TMapper */
        public readonly ExceptionMapper $mapper,
        public readonly ?LoggerInterface $errorReporter,
    ) {
    }

    /** @return ?T */
    public function map(Throwable $exception, object $object): mixed
    {
        try {
            return $this->mapper->map($exception, $object);
        } catch (Throwable $mapperFailure) {
            if (null !== $this->errorReporter) {
                $this->errorReporter->critical('Exception mapping failed.', [
                    'mapped_exception' => $exception,
                    'object_class' => $object::class,
                    'exception' => $mapperFailure,
                ]);

                return null;
            }

            throw $mapperFailure;
        }
    }
}
