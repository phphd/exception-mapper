<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\Tests;

use PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension;
use PhPhD\ExceptionMapper\Connector\Symfony\Tests\BundleTestCase;
use PhPhD\ExceptionMapper\ExceptionMapper;
use PhPhD\ExceptionMapper\FaultTolerant\FaultTolerantExceptionMapper;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\ExceptionMatchList;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\ExceptionToMatchListMapper;
use PhPhD\ExceptionToolkit\Unwrapper\Messenger\MessengerExceptionUnwrapper;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\VarExporter\LazyObjectInterface;

/**
 * @covers \PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperBundle
 * @covers \PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension
 * @covers \PhPhD\ExceptionMapper\FaultTolerant\FaultTolerantExceptionMapperDecorationCompilerPass
 *
 * @internal
 */
final class ExceptionToMatchListMapperServiceTest extends BundleTestCase
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
        $faultTolerantMapper = self::getContainer()->get(ExceptionMapper::class.'<'.ExceptionMatchList::class.'>');
        self::assertInstanceOf(FaultTolerantExceptionMapper::class, $faultTolerantMapper);

        $exceptionMapper = $faultTolerantMapper->mapper;

        if (PhdExceptionMapperExtension::nativeProxiesAreSupported()) {
            self::assertInstanceOf(ExceptionToMatchListMapper::class, $exceptionMapper);
        } else {
            self::assertNotInstanceOf(ExceptionToMatchListMapper::class, $exceptionMapper);
            self::assertInstanceOf(LazyObjectInterface::class, $exceptionMapper);
            self::assertInstanceOf(ExceptionToMatchListMapper::class, $exceptionMapper->initializeLazyObject());
        }
    }

    public function testExceptionUnwrapper(): void
    {
        $exceptionUnwrapper = self::getContainer()->get('phd_exception_mapper.exception_unwrapper');
        self::assertInstanceOf(LazyObjectInterface::class, $exceptionUnwrapper);
        self::assertFalse($exceptionUnwrapper->isLazyObjectInitialized());
        self::assertInstanceOf(MessengerExceptionUnwrapper::class, $exceptionUnwrapper->initializeLazyObject());
    }
}
