<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Condition\TraceOrigin;

use LogicException;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Condition\Blueprint\MatchConditionCompiler;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Condition\Blueprint\PreCompiledMatchConditionBlueprint;
use ReflectionMethod;
use ReflectionProperty;
use Throwable;

use function class_exists;
use function count;
use function explode;
use function function_exists;
use function method_exists;
use function property_exists;
use function sprintf;
use function str_starts_with;
use function substr;

/**
 * @internal
 *
 * @implements MatchConditionCompiler<Throwable>
 */
final class ExceptionTraceOriginMatchConditionCompiler implements MatchConditionCompiler
{
    /** @return ?PreCompiledMatchConditionBlueprint<Throwable> */
    public function compile(ReflectionProperty $property, Catch_ $catch): ?PreCompiledMatchConditionBlueprint
    {
        /** @var ?non-empty-list<?string> $origin */
        $origin = $catch->getFrom();

        if (null === $origin) {
            return null;
        }

        @[$originClassName, $originFunctionName] = $origin;

        if (null !== $e = self::originDoesNotExist($originClassName, $originFunctionName)) {
            throw $e;
        }

        $condition = new ExceptionTraceOriginMatchCondition($originClassName, $originFunctionName);

        return new PreCompiledMatchConditionBlueprint($condition);
    }

    /**
     * @phpstan-assert-if-false ?class-string $originClassName
     * @phpstan-assert-if-false ?non-empty-string $originFunctionName
     *
     * @psalm-assert ?class-string $originClassName
     * @psalm-assert ?non-empty-string $originFunctionName
     */
    private static function originDoesNotExist(?string $originClassName, ?string $originFunctionName): ?LogicException
    {
        if (null !== $originClassName) {
            /** @psalm-suppress ArgumentTypeCoercion ($originClassName is a class-string when classDoesNotExist() returned null) */
            return self::classDoesNotExist($originClassName)
                ?? self::classFunctionDoesNotExist($originClassName, $originFunctionName); // @phpstan-ignore argument.type (the same)
        }

        if (null !== $originFunctionName) {
            return self::functionDoesNotExist($originFunctionName);
        }

        return new LogicException('Exception trace origin `from:` must specify at least one of originClassName and originFunctionName.');
    }

    /**
     * @phpstan-assert-if-false class-string $originClassName
     * @psalm-assert class-string $originClassName
     */
    private static function classDoesNotExist(string $originClassName): ?LogicException
    {
        if (class_exists($originClassName)) {
            return null;
        }

        return new LogicException(sprintf('Specified origin class "%s" does not exist.', $originClassName));
    }

    /**
     * @param class-string $originClassName
     *
     * @phpstan-assert-if-false ?non-empty-string $originFunctionName
     * @psalm-assert ?non-empty-string $originFunctionName
     */
    private static function classFunctionDoesNotExist(string $originClassName, ?string $originFunctionName): ?LogicException
    {
        if (null === $originFunctionName) {
            return null;
        }

        if (self::referencesPropertyHook($originFunctionName)) {
            return self::propertyHookDoesNotExist($originClassName, $originFunctionName);
        }

        return self::methodDoesNotExist($originClassName, $originFunctionName);
    }

    private static function referencesPropertyHook(string $functionName): bool
    {
        return str_starts_with($functionName, '$');
    }

    /**
     * @param class-string $originClassName
     *
     * @phpstan-assert-if-false non-empty-string $originFunctionName
     * @psalm-assert non-empty-string $originFunctionName
     */
    private static function propertyHookDoesNotExist(string $originClassName, string $originFunctionName): ?LogicException
    {
        if (self::propertyHookExists($originClassName, $originFunctionName)) {
            return null;
        }

        return new LogicException(sprintf('Specified origin property hook "%s::%s" does not exist.', $originClassName, $originFunctionName));
    }

    /** @param class-string $className */
    private static function propertyHookExists(string $className, string $functionName): bool
    {
        // Property hooks are only available as of PHP 8.4
        if (\PHP_VERSION_ID < 80400) {
            return false;
        }

        $hookReference = explode('::', substr($functionName, 1), 2);

        if (2 !== count($hookReference)) {
            return false;
        }

        [$propertyName, $hookName] = $hookReference;

        if (!property_exists($className, $propertyName)) {
            return false;
        }

        $property = new ReflectionProperty($className, $propertyName);

        /** @psalm-suppress UndefinedMethod */
        /** @var array<non-empty-string,ReflectionMethod> $hooks */
        $hooks = $property->getHooks(); // @phpstan-ignore method.notFound (getHooks() is available as of PHP 8.4)

        return isset($hooks[$hookName]);
    }

    /**
     * @param class-string $originClassName
     *
     * @phpstan-assert-if-false non-empty-string $originFunctionName
     * @psalm-assert non-empty-string $originFunctionName
     */
    private static function methodDoesNotExist(string $originClassName, string $originFunctionName): ?LogicException
    {
        if (method_exists($originClassName, $originFunctionName)) {
            return null;
        }

        return new LogicException(sprintf('Specified origin method "%s::%s()" does not exist.', $originClassName, $originFunctionName));
    }

    /**
     * @phpstan-assert-if-false non-empty-string $originFunctionName
     * @psalm-assert non-empty-string $originFunctionName
     */
    private static function functionDoesNotExist(string $originFunctionName): ?LogicException
    {
        if (function_exists($originFunctionName)) {
            return null;
        }

        return new LogicException(sprintf('Specified origin function "%s" does not exist.', $originFunctionName));
    }
}
