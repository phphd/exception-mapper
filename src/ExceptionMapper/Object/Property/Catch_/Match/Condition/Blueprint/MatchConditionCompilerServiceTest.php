<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Blueprint;

use PhPhD\ExceptionMapper\Connector\Symfony\Tests\BundleTestCase;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Composite\CompositeMatchConditionCompiler;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\PHP\Enum\EnumValueMatchCondition;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\PHP\Enum\EnumValueMatchConditionCompiler;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\QualifiedMatchConditionCompiler;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Symfony\Uid\UidValueMatchCondition;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Symfony\Uid\UidValueMatchConditionCompiler;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Symfony\Validator\ValidationFailedExceptionMatchCondition;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Symfony\Validator\ValidationFailedExceptionMatchConditionCompiler;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Value\ExceptionValueMatchCondition;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Value\ExceptionValueMatchConditionCompiler;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Uid\Exception\InvalidArgumentException as InvalidUidException;
use Throwable;

use function class_exists;
use function krsort;
use function property_exists;

/**
 * @covers \PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperBundle
 * @covers \PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension
 *
 * @internal
 */
final class MatchConditionCompilerServiceTest extends BundleTestCase
{
    public function testConditionCompilers(): void
    {
        $matchConditionCompiler = self::getContainer()->get(MatchConditionCompiler::class.'<'.Throwable::class.'>');
        self::assertInstanceOf(CompositeMatchConditionCompiler::class, $matchConditionCompiler);

        $conditionCompilerRegistry = $this->getConditionCompilerRegistry($matchConditionCompiler);
        self::assertInstanceOf(ServiceLocator::class, $conditionCompilerRegistry);

        $providedServices = $conditionCompilerRegistry->getProvidedServices();
        krsort($providedServices);

        $expected = [
            ExceptionValueMatchCondition::class => ExceptionValueMatchConditionCompiler::class,
            ValidationFailedExceptionMatchCondition::class => ValidationFailedExceptionMatchConditionCompiler::class,
            UidValueMatchCondition::class => UidValueMatchConditionCompiler::class,
            EnumValueMatchCondition::class => EnumValueMatchConditionCompiler::class,
        ];

        if (!class_exists(InvalidUidException::class) || !property_exists(InvalidUidException::class, 'invalidValue')) {
            unset($expected[UidValueMatchCondition::class]);
        }

        self::assertSame($expected, $providedServices);
    }

    private function getConditionCompilerRegistry(CompositeMatchConditionCompiler $compiler): ContainerInterface // @phpstan-ignore missingType.generics
    {
        return $this->getQualifiedMatchConditionCompiler($compiler)->matchConditionCompilerRegistry;
    }

    private function getQualifiedMatchConditionCompiler(CompositeMatchConditionCompiler $compiler): QualifiedMatchConditionCompiler
    {
        /** @var array{2:QualifiedMatchConditionCompiler} $compilers */
        $compilers = $compiler->compilers;

        return $compilers[2];
    }
}
