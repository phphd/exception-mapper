<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Symfony\Uid;

use PhPhD\ExceptionMapper\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Blueprint\MatchConditionCompiler;
use PhPhD\ExceptionMapper\Object\Property\Type\PropertyType;
use PhPhD\ExceptionMapper\Object\Property\Type\Specification\AnyType;
use PhPhD\ExceptionMapper\Object\Property\Type\Specification\MixedType;
use ReflectionProperty;
use Symfony\Component\Uid\Exception\InvalidArgumentException as InvalidUidException;
use Webmozart\Assert\Assert;

/** @api */
const uid_value = UidValueMatchCondition::class;

/**
 * @internal
 *
 * @implements MatchConditionCompiler<InvalidUidException>
 */
final class UidValueMatchConditionCompiler implements MatchConditionCompiler
{
    public const EXCEPTION_TYPE_GUARANTEE_MESSAGE = 'UidValueMatchCondition can only be used for '.InvalidUidException::class;

    public const PROPERTY_TYPE_GUARANTEE_MESSAGE = 'UidValueMatchCondition requires a string property, got: %s';

    public function compile(ReflectionProperty $property, Catch_ $catch): UidValueMatchConditionBlueprint
    {
        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        Assert::isAOf($catch->getExceptionClass(), InvalidUidException::class, self::EXCEPTION_TYPE_GUARANTEE_MESSAGE);

        if (null !== $e = self::propertyType($property)->doesNotTake(self::stringOrMixed(), self::PROPERTY_TYPE_GUARANTEE_MESSAGE)) {
            throw $e;
        }

        static $blueprint = new UidValueMatchConditionBlueprint();

        /** @phpstan-var UidValueMatchConditionBlueprint */
        return $blueprint;
    }

    private static function propertyType(ReflectionProperty $property): PropertyType
    {
        return new PropertyType($property->getType());
    }

    private static function stringOrMixed(): AnyType
    {
        static $stringOrMixed;

        /** @phpstan-var AnyType */
        return $stringOrMixed ??= new AnyType([
            UidValueMatchConditionBlueprint::isString(),
            new MixedType(),
        ]);
    }
}
