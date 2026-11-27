<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\Tests;

use PhPhD\ExceptionMapper\Blueprint\Compiler\ExceptionMappingCompiler;
use PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension;
use PhPhD\ExceptionMapper\Connector\Symfony\Tests\PublicServiceCompilerPass;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\Tests\Stub\PullRequest\Branch;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\Tests\Stub\PullRequest\OpenPullRequestCommand;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\PropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Tests\Probe\ProbeSet;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @covers \PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\PropertyExceptionMappingCompiler
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\Compiler\CatchExceptionMappingCompiler
 * @covers \PhPhD\ExceptionMapper\Util\Iterator\IsEmptyIterable
 *
 * @internal
 */
final class PropertyExceptionMappingCompilerUnitTest extends TestCase
{
    private const PROBED_CLASSES = [
        OpenPullRequestCommand::class,
        Branch::class,
    ];

    /** @var ExceptionMappingCompiler<ReflectionProperty,PropertyExceptionMappingBlueprint> */
    private ExceptionMappingCompiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();

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

    /** @dataProvider catchAttributedPropertyDataProvider */
    public function testCatchAttributedPropertyIsIncludedRegardlessOfType(ReflectionProperty $property): void
    {
        $this->assertIsIncluded($property);
    }

    /** @return iterable<string,array{ReflectionProperty}> */
    public static function catchAttributedPropertyDataProvider(): iterable
    {
        return self::probes()->of(PropertyInclusionRule::CatchAttributedPropertyIsIncluded);
    }

    /** @dataProvider nonCatchAttributedPropertyDataProvider */
    public function testNonCatchAttributedPropertyIsSkipped(ReflectionProperty $property): void
    {
        $this->assertIsSkipped($property);
    }

    /** @return iterable<string,array{ReflectionProperty}> */
    public static function nonCatchAttributedPropertyDataProvider(): iterable
    {
        return self::probes()->of(PropertyInclusionRule::NonCatchAttributedPropertyIsSkipped);
    }

    private function assertIsIncluded(ReflectionProperty $property): void
    {
        $blueprint = $this->compiler->compile($property);

        self::assertInstanceOf(
            PropertyExceptionMappingBlueprint::class,
            $blueprint,
            ProbeSet::referenceOf($property).' must be included in the blueprint.',
        );
        self::assertSame(
            $property,
            $blueprint->property,
            ProbeSet::referenceOf($property).' blueprint must reference the very probed property.',
        );
    }

    private function assertIsSkipped(ReflectionProperty $property): void
    {
        self::assertNull(
            $this->compiler->compile($property),
            ProbeSet::referenceOf($property).' must be skipped from the blueprint.',
        );
    }

    private static function probes(): ProbeSet
    {
        return new ProbeSet(self::PROBED_CLASSES);
    }
}
