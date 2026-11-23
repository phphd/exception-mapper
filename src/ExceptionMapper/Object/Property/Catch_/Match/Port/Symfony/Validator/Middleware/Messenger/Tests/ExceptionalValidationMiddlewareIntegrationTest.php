<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\Symfony\Validator\Middleware\Messenger\Tests;

use PhPhD\ExceptionMapper\Connector\Symfony\Tests\BundleTestCase;
use PhPhD\ExceptionMapper\Connector\Symfony\Tests\PublicServiceCompilerPass;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\Symfony\Validator\Middleware\Exception\ExceptionalValidationFailedException;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\Symfony\Validator\Middleware\Messenger\ExceptionalValidationMiddleware;
use PhPhD\ExceptionMapper\Tests\Trait\CatchException;
use PhPhD\ExceptionMapper\Tests\Unit\Stub\Exception\AnException;
use PhPhD\ExceptionMapper\Tests\Unit\Stub\Exception\StaticPropertyMatchedException;
use PhPhD\ExceptionMapper\Tests\Unit\Stub\HandleableMessageStub;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use stdClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Throwable;

/**
 * @covers \PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperBundle
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\Symfony\Validator\Middleware\Messenger\ExceptionalValidationMiddleware
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\Symfony\Validator\Middleware\Messenger\Exception\ExceptionalValidationFailedMessengerException
 *
 * @internal
 */
final class ExceptionalValidationMiddlewareIntegrationTest extends BundleTestCase
{
    use CatchException;

    private ExceptionalValidationMiddleware $middleware;

    private MockObject $nextMiddleware;

    private StackMiddleware $stack;

    protected function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();

        /** @var ExceptionalValidationMiddleware $middleware */
        $middleware = $container->get('phd_exceptional_validation');
        $this->middleware = $middleware;

        $this->nextMiddleware = $this->createMock(MiddlewareInterface::class);
        $this->stack = new StackMiddleware([$this->middleware, $this->nextMiddleware]);
    }

    /** @return array<int,CompilerPassInterface> */
    protected static function compilerPasses(): array
    {
        return [new PublicServiceCompilerPass('phd_exceptional_validation')];
    }

    public function testHandlesTheException(): void
    {
        $envelope = Envelope::wrap(HandleableMessageStub::create());

        $handlerException = new AnException();

        $this->middlewareThrows($handlerException);

        $e = self::catchException(
            fn(): Envelope => $this->middleware->handle($envelope, $this->stack),
            ExceptionalValidationFailedException::class,
        );

        self::assertSame($handlerException, $e->getPrevious());
    }

    public function testHandlesTheExceptionWrappedIntoHandlerFailedException(): void
    {
        $messengerException = new HandlerFailedException(
            $envelope = Envelope::wrap(HandleableMessageStub::create()),
            [
                new AnException(), // handler exception 1
                new HandlerFailedException(
                    $envelope,
                    [new StaticPropertyMatchedException()], // handler exception 2
                ),
            ],
        );

        $this->middlewareThrows($messengerException);

        $e = self::catchException(
            fn(): Envelope => $this->middleware->handle($envelope, $this->stack),
            ExceptionalValidationFailedException::class,
        );

        self::assertSame(
            'Message of type "PhPhD\ExceptionMapper\Tests\Unit\Stub\HandleableMessageStub" has failed exceptional validation.',
            $e->getMessage(),
        );
        self::assertSame($messengerException, $e->getPrevious());
        self::assertSame($envelope->getMessage(), $e->getViolatingMessage());

        $violations = $e->getViolations();
        self::assertCount(2, $violations);

        self::assertSame('property', $violations->get(0)->getPropertyPath());
        self::assertSame('staticProperty', $violations->get(1)->getPropertyPath());
    }

    public function testRethrowsTheNextMiddlewareExceptionIfUnmatched(): void
    {
        $envelope = Envelope::wrap(HandleableMessageStub::create());

        $exception = new RuntimeException();
        $this->middlewareThrows($exception);

        $this->expectExceptionObject($exception);

        $this->middleware->handle($envelope, $this->stack);
    }

    public function testReturnsWhenNoExceptionIsThrown(): void
    {
        $envelope = Envelope::wrap(HandleableMessageStub::create());
        $resultEnvelope = Envelope::wrap(new stdClass());

        $this->middlewareReturns($envelope, $resultEnvelope);

        $result = $this->middleware->handle($envelope, $this->stack);

        self::assertSame($resultEnvelope, $result);
    }

    private function middlewareThrows(Throwable $exception): void
    {
        $this->nextMiddleware
            ->method('handle')
            ->willThrowException($exception)
        ;
    }

    private function middlewareReturns(Envelope $envelope, Envelope $resultEnvelope): void
    {
        $this->nextMiddleware
            ->expects(self::atLeastOnce())
            ->method('handle')
            ->willReturnMap([
                [
                    $envelope,
                    $this->stack,
                    $resultEnvelope,
                ],
            ])
        ;
    }
}
