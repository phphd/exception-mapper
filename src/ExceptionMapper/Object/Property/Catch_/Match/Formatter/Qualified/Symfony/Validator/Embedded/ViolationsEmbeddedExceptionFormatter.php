<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Symfony\Validator\Embedded;

use Closure;
use LogicException;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\ExceptionMatch;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;
use Throwable;

use function array_map;
use function iterator_to_array;

/** @api */
const embedded_violations = ViolationsEmbeddedExceptionFormatter::class;

/**
 * @internal - use {@see embedded_violations} constant for a class reference instead
 *
 * @implements ExceptionMatchFormatter<ViolationsEmbeddedException|ValidationFailedException,ConstraintViolationListInterface>
 */
final class ViolationsEmbeddedExceptionFormatter implements ExceptionMatchFormatter
{
    /** @api */
    public function __construct(
        /** @var ?Closure(string,array<array-key,mixed>):string */
        private readonly ?Closure $translate = null,
    ) {
    }

    /**
     * @phpstan-param ExceptionMatch<covariant ViolationsEmbeddedException|ValidationFailedException> $exceptionMatch
     * @psalm-param ExceptionMatch<ViolationsEmbeddedException|ValidationFailedException> $exceptionMatch
     */
    public function format(ExceptionMatch $exceptionMatch): ConstraintViolationListInterface
    {
        $exception = $exceptionMatch->getException();

        /** @var list<ConstraintViolationInterface> $violationList */
        $violationList = iterator_to_array($exception->getViolations());

        if ([] === $violationList) {
            throw new LogicException('Violation list must not be empty');
        }

        $node = $exceptionMatch->getNode();

        $root = $node->getRootObject();
        $propertyPath = $node->getPropertyPath()
            ->join('.')
        ;
        $redoTranslation = (null !== $this->translate) && $this->shouldRedoTranslation($exception);

        /** @psalm-suppress PossiblyNullFunctionCall */
        return new ConstraintViolationList(array_map(
            fn(ConstraintViolationInterface $violation): ConstraintViolation => new ConstraintViolation(
                $redoTranslation
                    ? ($this->translate)($violation->getMessageTemplate(), $violation->getParameters())
                    : $violation->getMessage(),
                $violation->getMessageTemplate(),
                $violation->getParameters(),
                $root,
                $propertyPath,
                $violation->getInvalidValue(),
                $violation->getPlural(),
                $violation->getCode(),
                $violation->getConstraint(),
                $violation->getCause(),
            ),
            $violationList,
        ));
    }

    /**
     * {@see Validation::createCallable()} instantiates a validator afresh
     * w/o the Translator configuration (i.e. locale set from Accept-Language header)
     */
    private function shouldRedoTranslation(Throwable $exception): bool
    {
        if (ValidationFailedException::class !== $exception::class) {
            return false;
        }

        return Validation::class === ($exception->getTrace()[0]['class'] ?? null);
    }
}
