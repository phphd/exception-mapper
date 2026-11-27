<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests;

use PhPhD\ExceptionMapper\Blueprint\Compiler\ExceptionMappingCompiler;
use PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension;
use PhPhD\ExceptionMapper\Connector\Symfony\Tests\PublicServiceCompilerPass;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\CascadePropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\Stub\Category\Subcategory;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\Stub\Order\PlaceOrderCommand;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\Stub\Order\Shipping\ShippingAddress;
use PhPhD\ExceptionMapper\Tests\Probe\ProbeSet;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @covers \PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\CascadePropertyExceptionMappingCompiler
 * @covers \PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Type\PossiblyExceptionMappedType
 * @covers \PhPhD\ExceptionMapper\Object\Property\Type\PropertyType
 * @covers \PhPhD\ExceptionMapper\Object\Property\Type\Specification\AnyType
 * @covers \PhPhD\ExceptionMapper\Object\Property\Type\Specification\MixedType
 * @covers \PhPhD\ExceptionMapper\Object\Property\Type\Specification\MixedObjectType
 * @covers \PhPhD\ExceptionMapper\Object\Property\Type\Specification\MixedIterableType
 *
 * @internal
 */
final class CascadeExceptionMappingCompilerUnitTest extends TestCase
{
    private const PROBED_CLASSES = [
        PlaceOrderCommand::class,
        ShippingAddress::class,
        Subcategory::class,
    ];

    /** @var ExceptionMappingCompiler<ReflectionProperty,CascadePropertyExceptionMappingBlueprint> */
    private ExceptionMappingCompiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();

        $container = (new PhdExceptionMapperExtension())->getContainer([
            'kernel.environment' => 'test',
            'kernel.build_dir' => __DIR__.'/var',
        ]);

        $container->addCompilerPass(new PublicServiceCompilerPass(ExceptionMappingCompiler::class.'<'.ReflectionProperty::class.','.CascadePropertyExceptionMappingBlueprint::class.'>'));

        $container->compile();

        /** @var ExceptionMappingCompiler<ReflectionProperty,CascadePropertyExceptionMappingBlueprint> $compiler */
        $compiler = $container->get(ExceptionMappingCompiler::class.'<'.ReflectionProperty::class.','.CascadePropertyExceptionMappingBlueprint::class.'>');
        $this->compiler = $compiler;
    }

    /**
     * @dataProvider looselyTypedPropertyDataProvider
     * @dataProvider iterableTypedPropertyDataProvider
     * @dataProvider relativeClassTypedPropertyDataProvider
     * @dataProvider interfaceTypedPropertyDataProvider
     * @dataProvider nonFinalClassTypedPropertyDataProvider
     * @dataProvider finalExceptionMappedClassTypedPropertyDataProvider
     */
    public function testPossiblyExceptionMappedPropertyIsIncluded(ReflectionProperty $property): void
    {
        $this->assertIsIncluded($property);
    }

    /** @return iterable<string,array{ReflectionProperty}> */
    public static function looselyTypedPropertyDataProvider(): iterable
    {
        return self::probes()->of(CascadeInclusionRule::LooselyTypedPropertyIsIncluded);
    }

    /** @return iterable<string,array{ReflectionProperty}> */
    public static function iterableTypedPropertyDataProvider(): iterable
    {
        return self::probes()->of(CascadeInclusionRule::IterableTypedPropertyIsIncluded);
    }

    /** @return iterable<string,array{ReflectionProperty}> */
    public static function relativeClassTypedPropertyDataProvider(): iterable
    {
        return self::probes()->of(CascadeInclusionRule::RelativeClassTypedPropertyIsIncluded);
    }

    /** @return iterable<string,array{ReflectionProperty}> */
    public static function interfaceTypedPropertyDataProvider(): iterable
    {
        return self::probes()->of(CascadeInclusionRule::InterfaceTypedPropertyIsIncluded);
    }

    /** @return iterable<string,array{ReflectionProperty}> */
    public static function nonFinalClassTypedPropertyDataProvider(): iterable
    {
        return self::probes()->of(CascadeInclusionRule::NonFinalClassTypedPropertyIsIncluded);
    }

    /** @return iterable<string,array{ReflectionProperty}> */
    public static function finalExceptionMappedClassTypedPropertyDataProvider(): iterable
    {
        return self::probes()->of(CascadeInclusionRule::FinalExceptionMappedClassTypedPropertyIsIncluded);
    }

    /**
     * @dataProvider scalarTypedPropertyDataProvider
     * @dataProvider enumTypedPropertyDataProvider
     * @dataProvider builtInClassTypedPropertyDataProvider
     * @dataProvider finalNonExceptionMappedClassTypedPropertyDataProvider
     */
    public function testNeverExceptionMappedPropertyIsSkipped(ReflectionProperty $property): void
    {
        self::assertNull(
            $this->compiler->compile($property),
            ProbeSet::referenceOf($property).' must be skipped from the blueprint.',
        );
    }

    /** @return iterable<string,array{ReflectionProperty}> */
    public static function scalarTypedPropertyDataProvider(): iterable
    {
        return self::probes()->of(CascadeInclusionRule::ScalarTypedPropertyIsSkipped);
    }

    /** @return iterable<string,array{ReflectionProperty}> */
    public static function enumTypedPropertyDataProvider(): iterable
    {
        return self::probes()->of(CascadeInclusionRule::EnumTypedPropertyIsSkipped);
    }

    /** @return iterable<string,array{ReflectionProperty}> */
    public static function builtInClassTypedPropertyDataProvider(): iterable
    {
        return self::probes()->of(CascadeInclusionRule::BuiltInClassTypedPropertyIsSkipped);
    }

    /** @return iterable<string,array{ReflectionProperty}> */
    public static function finalNonExceptionMappedClassTypedPropertyDataProvider(): iterable
    {
        return self::probes()->of(CascadeInclusionRule::FinalNonExceptionMappedClassTypedPropertyIsSkipped);
    }

    private function assertIsIncluded(ReflectionProperty $property): void
    {
        $blueprint = $this->compiler->compile($property);

        self::assertInstanceOf(
            CascadePropertyExceptionMappingBlueprint::class,
            $blueprint,
            ProbeSet::referenceOf($property).' must be included in the blueprint.',
        );
        self::assertSame(
            $property,
            $blueprint->property,
            ProbeSet::referenceOf($property).' blueprint must reference the very probed property.',
        );
    }

    private static function probes(): ProbeSet
    {
        return new ProbeSet(self::PROBED_CLASSES);
    }
}
