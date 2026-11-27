<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler;

use Closure;
use PhPhD\ExceptionMapper\Blueprint\Compiler\ExceptionMappingCompiler;
use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Blueprint\Registry\ObjectExceptionMappingBlueprintRegistry;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\Exception\PropertyExceptionMappingPrintCompilationFailedException;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\CascadePropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Type\PossiblyExceptionMappedType;
use PhPhD\ExceptionMapper\Object\Property\Type\PropertyType;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Reflector;
use Throwable;

/**
 * @internal
 *
 * @implements ExceptionMappingCompiler<ReflectionProperty,CascadePropertyExceptionMappingBlueprint>
 */
final class CascadePropertyExceptionMappingCompiler implements ExceptionMappingCompiler
{
    public function __construct(
        /** @var ObjectExceptionMappingBlueprintRegistry<object> */
        private readonly ObjectExceptionMappingBlueprintRegistry $printRegistry,
        private readonly ?LoggerInterface $errorReporter = null,
    ) {
    }

    public function reportingTo(LoggerInterface $reporter): self
    {
        return new self($this->printRegistry, $reporter);
    }

    /** @param ReflectionProperty $reflector */
    public function compile(Reflector $reflector): ?CascadePropertyExceptionMappingBlueprint
    {
        return $this->except($this->doCompile(...), $reflector);
    }

    /**
     * Nested classes are compiled on their own, so that cascade prints have nothing to force.
     *
     * @param CascadePropertyExceptionMappingBlueprint $blueprint
     */
    public function forceFullCompilation(ExceptionMappingBlueprint $blueprint): void
    {
    }

    private function doCompile(ReflectionProperty $property): ?CascadePropertyExceptionMappingBlueprint
    {
        if (!$this->propertyType($property)->takes(self::possiblyExceptionMappedType())) {
            return null;
        }

        return new CascadePropertyExceptionMappingBlueprint($property, $this->printRegistry);
    }

    private function propertyType(ReflectionProperty $property): PropertyType
    {
        return new PropertyType($property->getType());
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

    private static function possiblyExceptionMappedType(): PossiblyExceptionMappedType
    {
        static $possiblyExceptionMappedType = new PossiblyExceptionMappedType();

        /** @phpstan-var PossiblyExceptionMappedType */
        return $possiblyExceptionMappedType;
    }
}
