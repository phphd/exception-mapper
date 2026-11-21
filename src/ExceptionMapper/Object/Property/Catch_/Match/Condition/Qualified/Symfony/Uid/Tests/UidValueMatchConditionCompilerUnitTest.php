<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Symfony\Uid\Tests;

use InvalidArgumentException;
use LogicException;
use PhPhD\ExceptionMapper\Blueprint\Compiler\ExceptionMappingCompiler;
use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension;
use PhPhD\ExceptionMapper\Connector\Symfony\Tests\PublicServiceCompilerPass;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\Exception\PropertyExceptionMappingPrintCompilationFailedException;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\Exception\Tests\Trait\AssertPropertyCompilationFailedOn;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\PropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Symfony\Uid\Tests\Stub\CancelOrderCommand;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Symfony\Uid\UidValueMatchConditionCompiler;
use PhPhD\ExceptionMapper\Tests\Trait\CatchException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Uid\Exception\InvalidArgumentException as InvalidUidException;

use function class_exists;
use function property_exists;
use function sprintf;

/**
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Symfony\Uid\UidValueMatchConditionCompiler
 * @covers \PhPhD\ExceptionMapper\Object\Property\Type\PropertyType
 * @covers \PhPhD\ExceptionMapper\Object\Property\Type\Specification\StringType
 * @covers \PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\PropertyExceptionMappingCompiler
 * @covers \PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\Exception\PropertyExceptionMappingPrintCompilationFailedException
 * @covers \PhPhD\ExceptionMapper\Blueprint\Compiler\ExceptionMappingPrintCompilationFailedException::getRootException
 *
 * @internal
 */
final class UidValueMatchConditionCompilerUnitTest extends TestCase
{
    use CatchException;
    use AssertPropertyCompilationFailedOn;

    /** @var ExceptionMappingCompiler<ReflectionProperty,PropertyExceptionMappingBlueprint> */
    private ExceptionMappingCompiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists(InvalidUidException::class) || !property_exists(InvalidUidException::class, 'invalidValue')) {
            self::markTestSkipped('Installed version of Symfony Uid component does not fit for matching.');
        }

        $container = (new PhdExceptionMapperExtension())->getContainer([
            'kernel.environment' => 'test',
            'kernel.build_dir' => __DIR__.'/var',
        ]);

        $container->addCompilerPass(new PublicServiceCompilerPass(ExceptionMappingCompiler::class.'<'.ReflectionProperty::class.','.PropertyExceptionMappingBlueprint::class.'>'));

        $container->compile();

        /** @var ExceptionMappingCompiler<ReflectionProperty,PropertyExceptionMappingBlueprint> $compiler */
        $compiler = $container->get(ExceptionMappingCompiler::class.'<'.ReflectionProperty::class.','.PropertyExceptionMappingBlueprint::class.'>');
        $this->compiler = $compiler;
    }

    public function testThrowsWhenMatchConditionUsesNonInvalidUidExceptionClass(): void
    {
        $property = new ReflectionProperty(CancelOrderCommand::class, 'customerId');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(UidValueMatchConditionCompiler::EXCEPTION_TYPE_GUARANTEE_MESSAGE);

        $compilationException = self::catchException(
            fn(): ?ExceptionMappingBlueprint => $this->compiler->compile($property),
            PropertyExceptionMappingPrintCompilationFailedException::class,
        );

        self::assertPropertyCompilationFailedOn($property, $compilationException);

        throw $compilationException->getRootException();
    }

    public function testThrowsWhenPropertyTypeDoesNotCoverString(): void
    {
        $property = new ReflectionProperty(CancelOrderCommand::class, 'legacyOrderId');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf(UidValueMatchConditionCompiler::PROPERTY_TYPE_GUARANTEE_MESSAGE, 'int'));

        $compilationException = self::catchException(
            fn(): ?ExceptionMappingBlueprint => $this->compiler->compile($property),
            PropertyExceptionMappingPrintCompilationFailedException::class,
        );

        self::assertPropertyCompilationFailedOn($property, $compilationException);

        throw $compilationException->getRootException();
    }

    public function testCompilesWhenPropertyTypeIncludesString(): void
    {
        $property = new ReflectionProperty(CancelOrderCommand::class, 'orderId');

        $blueprint = $this->compiler->compile($property);

        self::assertInstanceOf(PropertyExceptionMappingBlueprint::class, $blueprint);
    }
}
