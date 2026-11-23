<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\FaultTolerant\Tests;

use LogicException;
use PhPhD\ExceptionMapper\Blueprint\Compiler\Log\SpyLogger;
use PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension;
use PhPhD\ExceptionMapper\ExceptionMapper;
use PhPhD\ExceptionMapper\FaultTolerant\Tests\Stub\LetMyPeopleGoCommand;
use PhPhD\ExceptionMapper\FaultTolerant\Tests\Stub\PharaohRefusedException;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\ExceptionMatchList;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @covers \PhPhD\ExceptionMapper\FaultTolerant\FaultTolerantExceptionMapperDecorationCompilerPass
 * @covers \PhPhD\ExceptionMapper\FaultTolerant\FaultTolerantExceptionMapper
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
    }

    /** @dataProvider mapperIds */
    public function testMappingFailureIsThrownWhenLoggerIsNotAvailableRegardlessOfDebugMode(string $mapperId): void
    {
        $this->container->compile();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(LetMyPeopleGoCommand::MAPPING_FAILURE_MESSAGE);

        $this->mapper($mapperId)->map(new PharaohRefusedException(), new LetMyPeopleGoCommand());
    }

    /** @dataProvider mapperIds */
    public function testMappingFailureIsReportedInFaultTolerantMode(string $mapperId): void
    {
        $spyLogger = new SpyLogger();
        $this->container->set('logger', $spyLogger);
        $this->container->compile();

        $mappedException = new PharaohRefusedException();

        self::assertNull($this->mapper($mapperId)->map($mappedException, new LetMyPeopleGoCommand()));

        $records = $spyLogger->flush();
        self::assertCount(1, $records);

        [$failureRecord] = $records;

        self::assertSame(LogLevel::CRITICAL, $failureRecord['level']);
        self::assertSame('Exception mapping failed.', $failureRecord['message']);
        self::assertInstanceOf(LogicException::class, $failureRecord['context']['exception']);
        self::assertSame(LetMyPeopleGoCommand::MAPPING_FAILURE_MESSAGE, $failureRecord['context']['exception']->getMessage());
        self::assertSame($mappedException, $failureRecord['context']['mapped_exception']);
        self::assertSame(LetMyPeopleGoCommand::class, $failureRecord['context']['object_class']);
    }

    /** @dataProvider mapperIds */
    public function testMappingFailureIsThrownInDebugMode(string $mapperId): void
    {
        $this->container->set('logger', new SpyLogger());
        $this->container->setParameter('kernel.debug', value: true);
        $this->container->compile();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(LetMyPeopleGoCommand::MAPPING_FAILURE_MESSAGE);

        $this->mapper($mapperId)->map(new PharaohRefusedException(), new LetMyPeopleGoCommand());
    }

    /** @dataProvider mapperIds */
    public function testMappingFailureIsThrownWhenDebugModeIsNotSet(string $mapperId): void
    {
        $this->container->set('logger', new SpyLogger());
        $this->container->getParameterBag()->remove('kernel.debug');
        $this->container->compile();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(LetMyPeopleGoCommand::MAPPING_FAILURE_MESSAGE);

        $this->mapper($mapperId)->map(new PharaohRefusedException(), new LetMyPeopleGoCommand());
    }

    /** @return iterable<string,array{string}> */
    public static function mapperIds(): iterable
    {
        yield 'match list mapper' => [ExceptionMapper::class.'<'.ExceptionMatchList::class.'>'];

        yield 'violation list mapper' => [ExceptionMapper::class.'<'.ConstraintViolationListInterface::class.'>'];
    }

    /** @return ExceptionMapper<mixed> */
    private function mapper(string $mapperId): ExceptionMapper
    {
        /** @var ExceptionMapper<mixed> $mapper */
        $mapper = $this->container->get($mapperId);

        return $mapper;
    }
}
