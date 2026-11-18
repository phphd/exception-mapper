<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Type;

use LogicException;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Type\Specification\MixedType;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Type\Specification\TypeSpecification;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

use function array_any;
use function sprintf;

final class PropertyType
{
    public function __construct(
        private readonly ?ReflectionType $type,
    ) {
    }

    public function doesNotTake(TypeSpecification $specification, string $message): ?LogicException
    {
        if ($this->takes($specification)) {
            return null;
        }

        return new LogicException(sprintf($message, (string)$this->type ?: 'mixed')); // @phpstan-ignore ternary.shortNotAllowed
    }

    public function takes(TypeSpecification $specification): bool
    {
        return $this->namedTypeTakes($specification)
            ?? $this->compoundTypeTakes($specification)
            ?? $this->isMixed();
    }

    private function namedTypeTakes(TypeSpecification $specification): ?bool
    {
        if (!$this->type instanceof ReflectionNamedType) {
            return null;
        }

        return $specification->isSatisfiedBy($this->type);
    }

    private function compoundTypeTakes(TypeSpecification $specification): ?bool
    {
        if (!$this->type instanceof ReflectionUnionType && !$this->type instanceof ReflectionIntersectionType) {
            return null;
        }

        return array_any(
            $this->type->getTypes(),
            fn(ReflectionType $reflectionType): bool => (new self($reflectionType))->takes($specification),
        );
    }

    private function isMixed(): bool
    {
        return (new MixedType())->isSatisfiedBy($this->type);
    }
}
