<?php

/** @noinspection PhpDocMissingThrowsInspection */

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified;

use LogicException;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\ExceptionMatch;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * @internal
 *
 * @template TException of Throwable
 *
 * @template TResult of mixed
 *
 * @implements ExceptionMatchFormatter<TException,TResult>
 */
final class ExceptionMatchQualifiedFormatter implements ExceptionMatchFormatter
{
    /** @api */
    public function __construct(
        /** @var ContainerInterface<class-string<ExceptionMatchFormatter<TException,TResult>>,ExceptionMatchFormatter<TException,TResult>> */
        private readonly ContainerInterface $formatterRegistry,
        /** @var ?class-string<ExceptionMatchFormatter<TException,TResult>> */
        private readonly ?string $defaultFormatter = null,
    ) {
    }

    public function format(ExceptionMatch $exceptionMatch): mixed
    {
        $catch = $exceptionMatch->getNode();

        $formatterId = $catch->getFormatterId() ?? $this->defaultFormatter;

        if (null === $formatterId) {
            throw new LogicException('No ExceptionMatchFormatter provided and no default one configured.');
        }

        if (null !== $e = $this->formatterIsNotRegistered($formatterId)) {
            throw $e;
        }

        return $this->formatterRegistry->get($formatterId)->format($exceptionMatch);
    }

    /**
     * @param class-string<ExceptionMatchFormatter<TException,mixed>> $formatterId
     *
     * @phpstan-assert-if-false class-string<ExceptionMatchFormatter<TException,TResult>> $formatterId
     * @psalm-assert class-string<ExceptionMatchFormatter<TException,TResult>> $formatterId
     *
     * @internal
     */
    public function formatterIsNotRegistered(string $formatterId): ?UnregisteredExceptionMatchFormatterException
    {
        /** @psalm-suppress ArgumentTypeCoercion - that's a fair catch: type must be narrowed */
        if ($this->formatterRegistry->has($formatterId)) {
            return null;
        }

        return new UnregisteredExceptionMatchFormatterException($formatterId);
    }
}
