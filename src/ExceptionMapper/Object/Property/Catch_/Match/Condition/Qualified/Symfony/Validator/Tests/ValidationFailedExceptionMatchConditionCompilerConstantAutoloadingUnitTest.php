<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Symfony\Validator\Tests;

use PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\Compiler\Autoload\ConstantsClassLoader;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Blueprint\MatchConditionCompiler;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Symfony\Validator\ValidationFailedExceptionMatchConditionCompiler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\Compiler\Autoload\ConstantsAutoloadingCompilerPass
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Autoload\QualifiedMatchConditionClassDiscovery
 *
 * @internal
 */
final class ValidationFailedExceptionMatchConditionCompilerConstantAutoloadingUnitTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();

        $container = (new PhdExceptionMapperExtension())->getContainer([
            'kernel.environment' => 'test',
            'kernel.build_dir' => __DIR__.'/var',
        ]);

        $this->container = $container;
    }

    public function testMatchConditionCompilerIsRecordedForTheConstantsAutoloading(): void
    {
        $this->container->compile();

        if (!$this->container->hasDefinition(MatchConditionCompiler::class.'<'.ValidationFailedException::class.'>')) {
            self::markTestSkipped('ValidationFailedExceptionMatchConditionCompiler is not registered');
        }

        /** @var list<class-string> $classNames */
        $classNames = $this->container->getDefinition(ConstantsClassLoader::class)
            ->getArgument(0)
        ;

        self::assertContains(ValidationFailedExceptionMatchConditionCompiler::class, $classNames);
    }
}
