<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler;

use Closure;
use Generator;
use PhPhD\ExceptionMapper\Blueprint\Compiler\ExceptionMappingCompiler;
use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\Exception\PropertyExceptionMappingPrintCompilationFailedException;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\PropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\CatchExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Util\Iterator\IsEmptyIterable;
use PhPhD\ExceptionMapper\Util\Iterator\MemoizingIteratorAggregate;
use Psr\Log\LoggerInterface;
use ReflectionAttribute;
use ReflectionProperty;
use Reflector;
use Throwable;

/**
 * @internal
 *
 * @implements ExceptionMappingCompiler<ReflectionProperty,PropertyExceptionMappingBlueprint>
 */
final class PropertyExceptionMappingCompiler implements ExceptionMappingCompiler
{
    public function __construct(
        /** @var ExceptionMappingCompiler<ReflectionAttribute<Catch_<Throwable,Throwable>>,CatchExceptionMappingBlueprint<Throwable>> */
        private readonly ExceptionMappingCompiler $catchCompiler,
        private readonly ?LoggerInterface $errorReporter = null,
    ) {
    }

    public function reportingTo(LoggerInterface $reporter): self
    {
        return new self($this->catchCompiler->reportingTo($reporter), $reporter);
    }

    /** @param ReflectionProperty $reflector */
    public function compile(Reflector $reflector): ?PropertyExceptionMappingBlueprint
    {
        return $this->except($this->doCompile(...), $reflector);
    }

    /** @param PropertyExceptionMappingBlueprint $blueprint */
    public function forceFullCompilation(ExceptionMappingBlueprint $blueprint): void
    {
        $this->except(function (ReflectionProperty $property) use ($blueprint): void {
            /** @psalm-suppress TooManyArguments */
            foreach ($blueprint->catchPrints as $catchPrint) {
                $this->catchCompiler->forceFullCompilation($catchPrint, $property); // @phpstan-ignore arguments.count
            }
        }, $blueprint->property);
    }

    private function doCompile(ReflectionProperty $property): ?PropertyExceptionMappingBlueprint
    {
        $catchPrints = new MemoizingIteratorAggregate($this->compileCatchPrints($property));

        $propertyPrint = new PropertyExceptionMappingBlueprint($property, $catchPrints);

        if ((new IsEmptyIterable($propertyPrint->catchPrints))()) {
            return null;
        }

        return $propertyPrint;
    }

    /**
     * @psalm-suppress UnnecessaryVarAnnotation
     *
     * @return Generator<int,CatchExceptionMappingBlueprint<Throwable>>
     */
    private function compileCatchPrints(ReflectionProperty $property): Generator
    {
        /** @phpstan-var ReflectionAttribute<Catch_<Throwable,Throwable>> $catchAttribute */
        foreach ($property->getAttributes(Catch_::class) as $catchAttribute) {
            /** @psalm-suppress TooManyArguments */
            if (null !== $catchPrint = $this->catchCompiler->compile($catchAttribute, $property)) { // @phpstan-ignore arguments.count
                yield $catchPrint;
            }
        }
    }

    /**
     * @template T
     *
     * @param Closure(ReflectionProperty):T $closure
     *
     * @return ?T
     */
    private function except(Closure $closure, ReflectionProperty $property): mixed
    {
        try {
            return $closure($property);
        } catch (Throwable $exception) {
            $e = new PropertyExceptionMappingPrintCompilationFailedException($property, $exception);

            if (null !== $this->errorReporter) {
                // One broken property won't spoil the whole match model.
                $this->errorReporter->error($e->getMessage(), ['exception' => $e]);

                return null;
            }

            throw $e;
        }
    }
}
