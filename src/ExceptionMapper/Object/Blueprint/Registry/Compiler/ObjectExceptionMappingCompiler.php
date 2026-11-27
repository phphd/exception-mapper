<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Blueprint\Registry\Compiler;

use Closure;
use Generator;
use LogicException;
use PhPhD\ExceptionMapper\Blueprint\Compiler\ExceptionMappingCompiler;
use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Blueprint\ObjectExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Blueprint\Registry\Compiler\Exception\ObjectExceptionMappingPrintCompilationFailedException;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\PropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\CascadePropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Try_;
use PhPhD\ExceptionMapper\Util\Iterator\IsEmptyIterable;
use PhPhD\ExceptionMapper\Util\Iterator\MemoizingIteratorAggregate;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionProperty;
use Reflector;
use Throwable;

/**
 * @internal
 *
 * @implements ExceptionMappingCompiler<ReflectionClass<object>,ObjectExceptionMappingBlueprint<object>>
 */
final class ObjectExceptionMappingCompiler implements ExceptionMappingCompiler
{
    public function __construct(
        /** @var ExceptionMappingCompiler<ReflectionProperty,PropertyExceptionMappingBlueprint> */
        private readonly ExceptionMappingCompiler $propertyCompiler,
        /** @var ExceptionMappingCompiler<ReflectionProperty,CascadePropertyExceptionMappingBlueprint> */
        private readonly ExceptionMappingCompiler $cascadePropertyCompiler,
        private readonly ?LoggerInterface $errorReporter = null,
    ) {
    }

    /** @param ReflectionClass<object> $reflectionClass */
    public static function isMappedClass(ReflectionClass $reflectionClass): bool
    {
        return [] !== $reflectionClass->getAttributes(Try_::class);
    }

    public function reportingTo(LoggerInterface $reporter): self
    {
        return new self(
            $this->propertyCompiler->reportingTo($reporter),
            $this->cascadePropertyCompiler->reportingTo($reporter),
            $reporter,
        );
    }

    /** @param ReflectionClass<object> $reflector */
    public function compile(Reflector $reflector): ?ObjectExceptionMappingBlueprint
    {
        return $this->except($this->doCompile(...), $reflector);
    }

    /** @param ObjectExceptionMappingBlueprint<object> $blueprint */
    public function forceFullCompilation(ExceptionMappingBlueprint $blueprint): void
    {
        $this->except(
            function () use ($blueprint): void {
                foreach ($blueprint->propertyPrints as $propertyPrint) {
                    $this->propertyCompiler->forceFullCompilation($propertyPrint);
                }

                // Iterating compiles every cascade print, so that broken ones get reported
                foreach ($blueprint->cascadePropertyPrints as $cascadePrint) {
                    $this->cascadePropertyCompiler->forceFullCompilation($cascadePrint);
                }
            },
            new ReflectionClass($blueprint->className),
        );
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return ?ObjectExceptionMappingBlueprint<object>
     */
    private function doCompile(ReflectionClass $reflectionClass): ?ObjectExceptionMappingBlueprint
    {
        if (!self::isMappedClass($reflectionClass)) {
            return null;
        }

        if ($reflectionClass->isAbstract()) {
            throw new LogicException('#[Try_] on an abstract class has no effect: the attribute is not inherited by the subclasses.');
        }

        $objectPrint = new ObjectExceptionMappingBlueprint(
            $reflectionClass->getName(),
            new MemoizingIteratorAggregate($this->compilePropertyPrints($reflectionClass)),
            new MemoizingIteratorAggregate($this->compileCascadePropertyPrints($reflectionClass)),
        );

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ((new IsEmptyIterable($objectPrint->propertyPrints))() && (new IsEmptyIterable($objectPrint->cascadePropertyPrints))()) {
            throw new LogicException('#[Try_] class does not define any #[Catch_] mappings and no cascade matchable properties, so it never matches.');
        }

        return $objectPrint;
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return Generator<int,PropertyExceptionMappingBlueprint>
     */
    private function compilePropertyPrints(ReflectionClass $reflectionClass): Generator
    {
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (null !== $propertyPrint = $this->propertyCompiler->compile($reflectionProperty)) {
                yield $propertyPrint;
            }
        }
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return Generator<int,CascadePropertyExceptionMappingBlueprint>
     */
    private function compileCascadePropertyPrints(ReflectionClass $reflectionClass): Generator
    {
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (null !== $cascadePropertyPrint = $this->cascadePropertyCompiler->compile($reflectionProperty)) {
                yield $cascadePropertyPrint;
            }
        }
    }

    /**
     * @template T
     *
     * @param Closure(ReflectionClass<object>):T $closure
     * @param ReflectionClass<object> $class
     *
     * @return ?T
     */
    private function except(Closure $closure, ReflectionClass $class): mixed
    {
        try {
            return $closure($class);
        } catch (Throwable $exception) {
            $e = new ObjectExceptionMappingPrintCompilationFailedException($class, $exception);

            if (null !== $this->errorReporter) {
                // One broken class mapping won't spoil the whole situation.
                $this->errorReporter->error($e->getMessage(), ['exception' => $e]);

                return null;
            }

            throw $e;
        }
    }
}
