<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Linter\Class;

use Generator;
use PhPhD\ExceptionMapper\Blueprint\Compiler\ExceptionMappingCompiler;
use PhPhD\ExceptionMapper\Linter\MappingLinter;
use PhPhD\ExceptionMapper\Linter\Report\Class\ClassReport;
use PhPhD\ExceptionMapper\Linter\Report\Defect\Location\DefectLocation;
use PhPhD\ExceptionMapper\Linter\Report\Defect\MappingDefect;
use PhPhD\ExceptionMapper\Linter\Report\LintReport;
use PhPhD\ExceptionMapper\Object\Blueprint\ObjectExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Blueprint\Registry\Compiler\ObjectExceptionMappingCompiler;
use PhPhD\ExceptionMapper\Object\Blueprint\Registry\ObjectExceptionMappingBlueprintRegistry;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\PropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Catch_;
use ReflectionClass;
use ReflectionProperty;

use function iterator_to_array;
use function sprintf;

/**
 * @internal
 *
 * @implements MappingLinter<class-string,LintReport>
 */
final class ClassMappingLinter implements MappingLinter
{
    /** @api */
    public function __construct(
        /** @var ObjectExceptionMappingBlueprintRegistry<object> */
        private readonly ObjectExceptionMappingBlueprintRegistry $printRegistry,
        /** @var ExceptionMappingCompiler<ReflectionClass<object>,ObjectExceptionMappingBlueprint<object>> */
        private readonly ExceptionMappingCompiler $objectCompiler,
        /** @var ExceptionMappingCompiler<ReflectionProperty,PropertyExceptionMappingBlueprint> */
        private readonly ExceptionMappingCompiler $propertyCompiler,
        private readonly CompilationDefectCollector $compilationDefectCollector,
    ) {
    }

    /** @param iterable<class-string> $symbols */
    public function lint(iterable $symbols): LintReport
    {
        $classReports = $this->lintClasses($symbols);

        return new LintReport(
            iterator_to_array($classReports, preserve_keys: false),
            $classReports->getReturn(),
        );
    }

    /**
     * A scanned class may hold defects of an inherited property, which belong to the class declaring it,
     * so every class is scanned before any of them is reported on.
     *
     * @param iterable<class-string> $classNames
     *
     * @return Generator<int,ClassReport,never,int>
     */
    private function lintClasses(iterable $classNames): Generator
    {
        /** @var array<class-string,non-empty-list<MappingDefect>> $classDefects */
        $classDefects = [];
        $scannedSymbols = 0;

        foreach ($classNames as $className) {
            foreach ($this->lintClass($className) as $defect) {
                $classDefects[$defect->getLocation()
                    ->getClassName()][] = $defect;
            }

            ++$scannedSymbols;
        }

        foreach ($classDefects as $className => $defects) {
            yield new ClassReport($className, $defects);
        }

        return $scannedSymbols;
    }

    /**
     * @param class-string $className
     *
     * @return Generator<MappingDefect>
     */
    private function lintClass(string $className): Generator
    {
        $reflectionClass = new ReflectionClass($className);

        yield from $this->lintStructure($reflectionClass);

        yield from $this->lintClassBlueprint($reflectionClass);
    }

    /**
     * @psalm-suppress NullableReturnStatement
     *
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return iterable<MappingDefect>
     */
    private function lintClassBlueprint(ReflectionClass $reflectionClass): iterable
    {
        if (null !== $print = $this->printRegistry->getMappingBlueprint($reflectionClass->getName())) {
            $this->objectCompiler->forceFullCompilation($print);

            return yield from $this->compilationDefectCollector->flushDefects();
        }

        // If no print, it possibly has failed compilation:
        if ([] !== $compilationDefects = $this->compilationDefectCollector->flushDefects()) {
            return yield from $compilationDefects;
        }

        // Or lacks #[Try] attribute:
        return yield from $this->possiblyMissingTryAttribute($reflectionClass);
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return list<MappingDefect>
     */
    private function possiblyMissingTryAttribute(ReflectionClass $reflectionClass): array
    {
        // The class does not compile, but it might be so due to a missing #[Try_] attribute
        $missingTryDefects = [];

        foreach ($reflectionClass->getProperties() as $property) {
            if (null !== $this->propertyCompiler->compile($property)) {
                // If class's property has a catch print,
                // but the class itself has no print, that's a defect
                $missingTryDefects[] = MappingDefect::warning(
                    'Properties declare #[Catch_] mappings, but the class is not marked with #[Try_] attribute, so it never matches.',
                    DefectLocation::atProperty($property),
                );
            }
        }

        return $missingTryDefects;
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return Generator<MappingDefect>
     */
    private function lintStructure(ReflectionClass $reflectionClass): Generator
    {
        if (!ObjectExceptionMappingCompiler::isMappedClass($reflectionClass)) {
            return;
        }

        yield from $this->lintParentPrivateCatches($reflectionClass);
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     *
     * @return Generator<MappingDefect>
     */
    private function lintParentPrivateCatches(ReflectionClass $reflectionClass): Generator
    {
        for ($parent = $reflectionClass->getParentClass(); false !== $parent; $parent = $parent->getParentClass()) {
            foreach ($parent->getProperties(ReflectionProperty::IS_PRIVATE) as $parentProperty) {
                if ($this->hasCatchAttributes($parentProperty)) {
                    yield MappingDefect::warning(
                        sprintf(
                            'Private property %s::$%s declares #[Catch_] mappings that are invisible to %s.',
                            $parent->getName(),
                            $parentProperty->getName(),
                            $reflectionClass->getName(),
                        ),
                        new DefectLocation($reflectionClass->getName(), $parentProperty->getName()),
                    );
                }
            }
        }
    }

    private function hasCatchAttributes(ReflectionProperty $reflectionProperty): bool
    {
        return [] !== $reflectionProperty->getAttributes(Catch_::class);
    }
}
