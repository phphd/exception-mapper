<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\FaultTolerant\Tests;

use LogicException;
use PhPhD\ExceptionMapper\Blueprint\Compiler\Log\SpyLogger;
use PhPhD\ExceptionMapper\ExceptionMapper;
use PhPhD\ExceptionMapper\FaultTolerant\FaultTolerantExceptionMapper;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RuntimeException;
use stdClass;

/**
 * @covers \PhPhD\ExceptionMapper\FaultTolerant\FaultTolerantExceptionMapper
 *
 * @internal
 */
final class FaultTolerantExceptionMapperUnitTest extends TestCase
{
    /** @var ExceptionMapper<mixed>&Stub */
    private ExceptionMapper&Stub $decoratedMapper;

    private SpyLogger $errorReporter;

    /** @var FaultTolerantExceptionMapper<mixed,ExceptionMapper<mixed>> */
    private FaultTolerantExceptionMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decoratedMapper = self::createStub(ExceptionMapper::class);
        $this->errorReporter = new SpyLogger();
        $this->mapper = new FaultTolerantExceptionMapper($this->decoratedMapper, $this->errorReporter);
    }

    public function testMapperFailureIsPropagatedUpWhenErrorReporterIsNotAvailable(): void
    {
        $mapperFailure = new LogicException('The match condition has failed.');
        $this->mapperThrows($mapperFailure);

        $mapper = new FaultTolerantExceptionMapper($this->decoratedMapper, errorReporter: null);

        $this->expectExceptionObject($mapperFailure);

        $mapper->map(new RuntimeException(), new stdClass());
    }

    public function testMappingFailureIsReportedAndNullIsReturned(): void
    {
        $mapperFailure = new LogicException('The match condition has failed.');
        $this->mapperThrows($mapperFailure);

        $null = $this->mapper->map($exception = new RuntimeException(), new stdClass());
        self::assertNull($null);

        self::assertSame([[
            'level' => LogLevel::CRITICAL,
            'message' => 'Exception mapping failed.',
            'context' => [
                'mapped_exception' => $exception,
                'object_class' => stdClass::class,
                'exception' => $mapperFailure,
            ],
        ]], $this->errorReporter->flush());
    }

    public function testMappingResultIsReturnedThrough(): void
    {
        $this->mapperReturns($mapperResult = 'result');

        self::assertSame($mapperResult, $this->mapper->map(new RuntimeException(), new stdClass()));
        self::assertSame([], $this->errorReporter->flush());
    }

    private function mapperThrows(LogicException $mapperFailure): void
    {
        $this->decoratedMapper->method('map')->willThrowException($mapperFailure);
    }

    private function mapperReturns(string $mapperResult): void
    {
        $this->decoratedMapper->method('map')->willReturn($mapperResult);
    }
}
