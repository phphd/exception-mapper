<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry;

use LogicException;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use Throwable;
use Webmozart\Assert\Assert;

use function preg_match;
use function preg_quote;
use function sprintf;
use function str_starts_with;

/** @internal */
final class ExceptionMatchFormatterClassGenericName
{
    private const GENERIC_CLASS_NAME_FORMAT = ExceptionMatchFormatter::class.'<TException,TResult>';

    /** @var array{TException:class-string<Throwable>,TResult:string} */
    private readonly array $classArguments;

    public function __construct(
        /** A generic formatter class name */
        string $className,
    ) {
        Assert::true(self::isArgumentQualifiedName($className));

        $this->classArguments = self::parseArguments($className);
    }

    public static function getBaseClassName(): string
    {
        return ExceptionMatchFormatter::class;
    }

    public static function isArgumentQualifiedName(string $name): bool
    {
        return str_starts_with($name, ExceptionMatchFormatter::class.'<');
    }

    public function getParameterTypesQualifiedClassName(): string
    {
        return ExceptionMatchFormatter::class.'<'.$this->getExceptionType().','.$this->getResultType().'>';
    }

    public function getResultTypeQualifiedClassName(): string
    {
        return ExceptionMatchFormatter::class.'<?,'.$this->getResultType().'>';
    }

    /** @return class-string<Throwable> */
    public function getExceptionType(): string
    {
        return $this->classArguments['TException'];
    }

    public function getResultType(): string
    {
        return $this->classArguments['TResult'];
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
