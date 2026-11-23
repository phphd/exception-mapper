<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\FaultTolerant\Tests;

use PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension;
use PhPhD\ExceptionMapper\FaultTolerant\FaultTolerantExceptionMapper;
use PhPhD\ExceptionMapper\FaultTolerant\Tests\Stub\FailingClientExceptionMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @covers \PhPhD\ExceptionMapper\FaultTolerant\FaultTolerantExceptionMapperDecorationCompilerPass
 * @covers \PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension
 *
 * @internal
 */
final class FaultTolerantExceptionMapperDecorationCompilerPassUnitTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = (new PhdExceptionMapperExtension())->getContainer([
            'kernel.environment' => 'test',
            'kernel.build_dir' => __DIR__.'/var',
            'kernel.debug' => false,
        ]);

        $this->container
            ->register(FailingClientExceptionMapper::class, FailingClientExceptionMapper::class)
            ->setAutoconfigured(autoconfigured: true)
            ->setPublic(boolean: true)
        ;
    }

    /** Debug mode allows exception shadowing so that mapper's exception root cause can be solved */
    public function testMappersAreNotFaultTolerantInDebugMode(): void
    {
        $this->container->setParameter('kernel.debug', value: true);
        $this->container->compile();

        self::assertNotInstanceOf(FaultTolerantExceptionMapper::class, $this->container->get(FailingClientExceptionMapper::class));
    }

    /** Standalone-using clients not specifying debug mode should encounter mapper's exception (even if it shadows) */
    public function testMappersAreNotFaultTolerantWhenDebugModeIsUnset(): void
    {
        $this->container->getParameterBag()->remove('kernel.debug');
        $this->container->compile();

        self::assertNotInstanceOf(FaultTolerantExceptionMapper::class, $this->container->get(FailingClientExceptionMapper::class));
    }

    /** In production, we'd want to avoid exception shadowing */
    public function testMappersAreFaultTolerantWhenDebugModeIsFalse(): void
    {
        $this->container->compile();

        self::assertInstanceOf(FaultTolerantExceptionMapper::class, $this->container->get(FailingClientExceptionMapper::class));
    }
}
