<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry;

use LogicException;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use Throwable;
use Webmozart\Assert\Assert;

use function is_a;
use function preg_match;
use function preg_quote;
use function sprintf;
use function str_starts_with;

/** @internal */
final class ExceptionMatchFormatterClassGenericName
{
    private const GENERIC_CLASS_NAME_FORMAT = ExceptionMatchFormatter::class.'<TException,TResult>';

    public function __construct(
        /** @var class-string<Throwable> */
        private readonly string $exceptionType,
        private readonly string $resultType,
    ) {
    }

    public static function fromArgumentQualifiedClassName(string $className): self
    {
        Assert::true(self::isArgumentQualifiedClassName($className));

        $classArguments = self::parseArguments($className);

        return new self($classArguments['TException'], $classArguments['TResult']);
    }

    public static function getBaseClassName(): string
    {
        return ExceptionMatchFormatter::class;
    }

    public static function isArgumentQualifiedClassName(string $name): bool
    {
        return str_starts_with($name, ExceptionMatchFormatter::class.'<');
    }

    /** @return class-string<Throwable> */
    public function getExceptionType(): string
    {
        return $this->exceptionType;
    }

    public function getResultType(): string
    {
        return $this->resultType;
    }

    /** @param class-string<Throwable> $exceptionClass */
    public function acceptsException(string $exceptionClass): bool
    {
        return is_a($exceptionClass, $this->exceptionType, allow_string: true);
    }

    public function getArgumentTypesQualifiedClassName(): string
    {
        return ExceptionMatchFormatter::class.'<'.$this->exceptionType.','.$this->resultType.'>';
    }

    public function getResultTypeQualifiedClassName(): string
    {
        return ExceptionMatchFormatter::class.'<?,'.$this->resultType.'>';
    }

    /** @return array{TException:class-string<Throwable>,TResult:string} */
    private static function parseArguments(string $className): array
    {
        $typeNamePattern = '[\w\\\\]+';
        $genericClassNamePattern = '/^'.preg_quote(ExceptionMatchFormatter::class, '/').'<(?<TException>'.$typeNamePattern.'),(?<TResult>'.$typeNamePattern.')>$/';

        if (1 !== preg_match($genericClassNamePattern, $className, $typeArguments)) {
            throw new LogicException(sprintf('Generic type "%s" must match "%s" format.', $className, self::GENERIC_CLASS_NAME_FORMAT));
        }

        /** @var array{TException:class-string<Throwable>,TResult:string} $typeArguments */
        return $typeArguments;
    }
}
