<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\Symfony\Validator;

use Generator;
use PhPhD\ExceptionMapper\ExceptionMapper;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\ExceptionMatchList;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;

/**
 * @internal
 *
 * @implements ExceptionMapper<ConstraintViolationListInterface>
 */
final class ExceptionToViolationListMapper implements ExceptionMapper
{
    /** @api */
    public function __construct(
        /** @var ExceptionMapper<ExceptionMatchList> */
        private readonly ExceptionMapper $mapper,
        /** @var ExceptionMatchFormatter<Throwable,ConstraintViolationInterface|ConstraintViolationListInterface> */
        private readonly ExceptionMatchFormatter $formatter,
    ) {
    }

    public function map(Throwable $exception, object $object): ?ConstraintViolationListInterface
    {
        $exceptionMatches = $this->mapper->map($exception, $object);

        if (null === $exceptionMatches) {
            return null;
        }

        $violations = $this->unwrapViolations($exceptionMatches->format($this->formatter));

        return new ConstraintViolationList($violations);
    }

    /**
     * @param iterable<ConstraintViolationInterface|ConstraintViolationListInterface> $violations
     *
     * @return Generator<ConstraintViolationInterface>
     */
    private function unwrapViolations(iterable $violations): Generator
    {
        foreach ($violations as $violationOrList) {
            if ($violationOrList instanceof ConstraintViolationListInterface) {
                yield from $violationOrList;
            } else {
                yield $violationOrList;
            }
        }
    }
}
