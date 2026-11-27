<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Linter;

use PhPhD\ExceptionMapper\Blueprint\Compiler\ExceptionMappingCompiler;
use PhPhD\ExceptionMapper\Linter\Class\ClassMappingLinter;
use PhPhD\ExceptionMapper\Linter\Class\CompilationDefectCollector;
use PhPhD\ExceptionMapper\Linter\Report\LintReport;
use PhPhD\ExceptionMapper\Object\Blueprint\ObjectExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Blueprint\Registry\Compiler\CompilingObjectExceptionMappingBlueprintRegistry;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\PropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Util\Log\SpyLogger;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services
        ->set(MappingLinter::class.'<class-string,'.LintReport::class.'>', ClassMappingLinter::class)
        ->arg(
            '$printRegistry',
            inline_service(CompilingObjectExceptionMappingBlueprintRegistry::class)
                ->arg('$compiler', service(ClassMappingLinter::class.'::$objectCompiler')),
        )->arg('$objectCompiler', service(ClassMappingLinter::class.'::$objectCompiler'))
        ->arg(
            '$propertyCompiler',
            inline_service(ExceptionMappingCompiler::class)
                ->factory([
                    service(ExceptionMappingCompiler::class.'<'.ReflectionProperty::class.','.PropertyExceptionMappingBlueprint::class.'>'),
                    'reportingTo',
                ])->args([service(CompilationDefectCollector::class.'::$compilationLogger')]),
        )->arg('$compilationDefectCollector', service(CompilationDefectCollector::class))
        ->tag(MappingLinter::class, ['id' => 'class-string'])
    ;

    $services // Lint-mode compiler - collects and reports mapping problems
        ->set(ClassMappingLinter::class.'::$objectCompiler', ExceptionMappingCompiler::class)
        ->factory([
            service(ExceptionMappingCompiler::class.'<'.ReflectionClass::class.','.ObjectExceptionMappingBlueprint::class.'>'),
            'reportingTo',
        ])->args([service(CompilationDefectCollector::class.'::$compilationLogger')])
    ;

    $services
        ->set($id = CompilationDefectCollector::class, $id)
        ->arg('$compilationLogger', service(CompilationDefectCollector::class.'::$compilationLogger'))
    ;

    // Linter's own logger to collect compilation exceptions
    $services->set(CompilationDefectCollector::class.'::$compilationLogger', SpyLogger::class);
};
