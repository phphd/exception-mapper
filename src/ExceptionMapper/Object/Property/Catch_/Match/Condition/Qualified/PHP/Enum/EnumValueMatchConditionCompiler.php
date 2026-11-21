<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\PHP\Enum;

use BackedEnum;
use LogicException;
use PhPhD\ExceptionMapper\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Blueprint\MatchConditionCompiler;
use PhPhD\ExceptionMapper\Object\Property\Type\PropertyType;
use PhPhD\ExceptionMapper\Object\Property\Type\Specification\AnyType;
use PhPhD\ExceptionMapper\Object\Property\Type\Specification\MixedType;
use ReflectionEnum;
use ReflectionProperty;
use ValueError;
use Webmozart\Assert\Assert;

use function is_a;
use function sprintf;
use function var_export;

/** @api */
const enum_value = EnumValueMatchCondition::class;

/**
 * @internal
 *
 * @implements MatchConditionCompiler<ValueError>
 */
final class EnumValueMatchConditionCompiler implements MatchConditionCompiler
{
    public const EXCEPTION_TYPE_GUARANTEE_MESSAGE = 'EnumValueMatchCondition can only be used for '.ValueError::class;

    public const PROPERTY_TYPE_GUARANTEE_MESSAGE = 'EnumValueMatchCondition requires an int|string property, got: %s';

    public function compile(ReflectionProperty $property, Catch_ $catch): EnumValueMatchConditionBlueprint
    {
        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        Assert::isAOf($catch->getExceptionClass(), ValueError::class, self::EXCEPTION_TYPE_GUARANTEE_MESSAGE);

        @[$enumClassName, $fromMethod] = $catch->getFrom(); // @phpstan-ignore offsetAccess.nonArray

        if (null !== $e = self::isNotEnumClass($enumClassName)) {
            throw $e;
        }

        if (null !== $e = self::isNotEnumFromMethod($enumClassName, $fromMethod)) {
            throw $e;
        }

        if (null !== $e = self::propertyType($property)->doesNotTake(self::intOrStringOrMixed(), self::PROPERTY_TYPE_GUARANTEE_MESSAGE)) {
            throw $e;
        }

        return new EnumValueMatchConditionBlueprint($enumClassName);
    }

    /**
     * @phpstan-assert-if-false class-string<BackedEnum> $className
     * @psalm-assert class-string<BackedEnum> $className
     */
    private static function isNotEnumClass(?string $className): ?LogicException
    {
        if (null !== $className && is_a($className, BackedEnum::class, allow_string: true)) {
            return null;
        }

        return new LogicException(sprintf(
            'EnumValueMatchCondition requires `from:` to contain a class-string of a BackedEnum, got: %s',
            var_export($className, return: true),
        ));
    }

    /** @param class-string<BackedEnum> $enumClassName */
    private static function isNotEnumFromMethod(string $enumClassName, ?string $fromMethod): ?LogicException
    {
        if (null === $fromMethod || 'from' === $fromMethod) {
            return null;
        }

        $enumReflection = new ReflectionEnum($enumClassName);

        return new LogicException(sprintf(
            'EnumValueMatchCondition must specify `from: [%1$s::class, \'from\']`, got: `from: [%1$s::class, \'%2$s\']`.',
            $enumReflection->getShortName(),
            $fromMethod,
        ));
    }

    private static function propertyType(ReflectionProperty $property): PropertyType
    {
        return new PropertyType($property->getType());
    }

    private static function intOrStringOrMixed(): AnyType
    {
        static $intOrStringOrMixed;

        /** @phpstan-var AnyType */
        return $intOrStringOrMixed ??= new AnyType([
            EnumValueMatchConditionBlueprint::isInt(),
            EnumValueMatchConditionBlueprint::isString(),
            new MixedType(),
        ]);
    }
}
