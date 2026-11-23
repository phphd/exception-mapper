<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Symfony\Validator;

use PhPhD\ExceptionMapper\Connector\Symfony\Tests\BundleTestCase;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\ExceptionMatchQualifiedFormatter;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\Container\ChainContainer;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Symfony\Validator\Embedded\ViolationsEmbeddedException;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Symfony\Validator\Embedded\ViolationsEmbeddedExceptionFormatter;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Symfony\Validator\Violation\ExceptionMatchToViolationFormatter;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Tests\Stub\Cart\OutOfStockExceptionMatchFormatter;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Tests\Stub\Cart\OutOfStockExceptionMatchFormatterCompilerPass;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Tests\Stub\Message\ExceptionMessageFormatter;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Tests\Stub\Message\ExceptionMessageFormatterCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

use function krsort;

/**
 * @covers \PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperBundle
 * @covers \PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension
 *
 * @internal
 */
final class ExceptionMatchToViolationQualifiedFormatterServiceTest extends BundleTestCase
{
    /** @return array<int,CompilerPassInterface> */
    protected static function compilerPasses(): array
    {
        return [
            ExceptionMessageFormatterCompilerPass::PRIORITY + 1 => new ExceptionMessageFormatterCompilerPass(),
            OutOfStockExceptionMatchFormatterCompilerPass::PRIORITY => new OutOfStockExceptionMatchFormatterCompilerPass(),
        ];
    }

    /** @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\Container\ChainContainer::getProvidedServices */
    public function testViolationFormatters(): void
    {
        $exceptionMatchToViolationQualifiedFormatter = $this->get(ExceptionMatchFormatter::class.'<?,'.ConstraintViolationInterface::class.'|'.ConstraintViolationListInterface::class.'>');
        self::assertInstanceOf(ExceptionMatchQualifiedFormatter::class, $exceptionMatchToViolationQualifiedFormatter);

        $defaultFormatter = $this->get(ExceptionMatchFormatter::class.'<'.Throwable::class.','.ConstraintViolationInterface::class.'>');
        self::assertInstanceOf(ExceptionMatchToViolationFormatter::class, $defaultFormatter);

        $violationsEmbeddedExceptionFormatter = $this->get(ExceptionMatchFormatter::class.'<'.ViolationsEmbeddedException::class.','.ConstraintViolationListInterface::class.'>');
        self::assertInstanceOf(ViolationsEmbeddedExceptionFormatter::class, $violationsEmbeddedExceptionFormatter);

        $validationFailedExceptionFormatter = $this->get(ExceptionMatchFormatter::class.'<'.ValidationFailedException::class.','.ConstraintViolationListInterface::class.'>');
        self::assertInstanceOf(ViolationsEmbeddedExceptionFormatter::class, $validationFailedExceptionFormatter);

        self::assertSame($violationsEmbeddedExceptionFormatter, $validationFailedExceptionFormatter);

        $formatterRegistry = $exceptionMatchToViolationQualifiedFormatter->formatterRegistry;
        self::assertInstanceOf(ChainContainer::class, $formatterRegistry);

        $providedServices = $formatterRegistry->getProvidedServices();
        krsort($providedServices);
        self::assertNotContains(
            ExceptionMessageFormatter::class,
            $providedServices, // excluded from the registry because of different TResult
        );
        self::assertSame([
            OutOfStockExceptionMatchFormatter::class => OutOfStockExceptionMatchFormatter::class,
            ExceptionMatchToViolationFormatter::class => ExceptionMatchToViolationFormatter::class,
            ViolationsEmbeddedExceptionFormatter::class => ViolationsEmbeddedExceptionFormatter::class,
        ], $providedServices);

        /** @psalm-suppress InvalidArgument */
        self::assertSame($defaultFormatter, $formatterRegistry->get(ExceptionMatchToViolationFormatter::class));
    }

    private function get(string $id): mixed
    {
        return self::getContainer()->get($id);
    }
}
