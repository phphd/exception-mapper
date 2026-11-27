<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Type;

use PhPhD\ExceptionMapper\Object\Blueprint\Registry\Compiler\ObjectExceptionMappingCompiler;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\CascadePropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Type\Specification\AnyType;
use PhPhD\ExceptionMapper\Object\Property\Type\Specification\MixedType;
use PhPhD\ExceptionMapper\Object\Property\Type\Specification\TypeSpecification;
use ReflectionClass;
use ReflectionNamedType;

use function in_array;

/** @internal */
final class PossiblyExceptionMappedType implements TypeSpecification
{
    /** @codeCoverageIgnore - not used */
    public function __invoke(mixed $value): bool
    {
        if (CascadePropertyExceptionMappingBlueprint::isIterable()($value)) {
            return true;
        }

        if (CascadePropertyExceptionMappingBlueprint::isObject()($value)) {
            return ObjectExceptionMappingCompiler::isMappedClass(new ReflectionClass($value::class));
        }

        return false;
    }

    public function isSatisfiedBy(ReflectionNamedType $type): bool
    {
        if (self::isObjectOrIterableOrMixed()->isSatisfiedBy($type)) {
            return true;
        }

        $className = $type->getName();

        if (in_array($className, ['self', 'parent'], strict: true)) { // Trait-declared properties
            return true;
        }

        if ($type->isBuiltin()) {
            return false;
        }

        /** @var class-string $className */
        $reflectionClass = new ReflectionClass($className);

        return match (true) {
            // Enums don't allow properties
            $reflectionClass->isEnum(),
            // Built-in classes / interface's implementations go w/o #[Try_]
            $reflectionClass->isInternal() => false,
            // Interfaces might have any implementor - including those with #[Try_]
            $reflectionClass->isInterface(),
            // Non-final classes might have any extender - including those with #[Try_]
            !$reflectionClass->isFinal() => true,
            default => ObjectExceptionMappingCompiler::isMappedClass($reflectionClass),
        };
    }

    private static function isObjectOrIterableOrMixed(): TypeSpecification
    {
        static $mixedTypesSpecification;

        /** @phpstan-var AnyType */
        return $mixedTypesSpecification ??= new AnyType([
            CascadePropertyExceptionMappingBlueprint::isObject(),
            CascadePropertyExceptionMappingBlueprint::isIterable(),
            new MixedType(),
        ]);
    }
}
