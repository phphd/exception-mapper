<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\Symfony\Validator;

use PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension;
use PhPhD\ExceptionMapper\Connector\Symfony\Tests\BundleTestCase;
use PhPhD\ExceptionMapper\ExceptionMapper;
use PhPhD\ExceptionMapper\FaultTolerant\FaultTolerantExceptionMapper;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\VarExporter\LazyObjectInterface;

/**
 * @covers \PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperBundle
 * @covers \PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension
 * @covers \PhPhD\ExceptionMapper\FaultTolerant\FaultTolerantExceptionMapperDecorationCompilerPass
 *
 * @internal
 */
final class ExceptionToViolationListMapperServiceTest extends BundleTestCase
{
    private const KERNEL_OPTIONS = [
        'debug' => false,
    ];

    /** @param array<array-key,mixed> $options */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return parent::createKernel(self::KERNEL_OPTIONS + $options);
    }

    public function testExceptionMapperService(): void
    {
        $faultTolerantMapper = self::getContainer()->get(ExceptionMapper::class.'<'.ConstraintViolationListInterface::class.'>');
        self::assertInstanceOf(FaultTolerantExceptionMapper::class, $faultTolerantMapper);

        $exceptionMapper = $faultTolerantMapper->mapper;

        if (PhdExceptionMapperExtension::nativeProxiesAreSupported()) {
            self::assertInstanceOf(ExceptionToViolationListMapper::class, $exceptionMapper);
        } else {
            self::assertNotInstanceOf(ExceptionToViolationListMapper::class, $exceptionMapper);
            self::assertInstanceOf(LazyObjectInterface::class, $exceptionMapper);
            self::assertInstanceOf(ExceptionToViolationListMapper::class, $exceptionMapper->initializeLazyObject());
        }
    }
}
