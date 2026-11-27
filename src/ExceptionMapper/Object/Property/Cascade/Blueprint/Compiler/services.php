<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler;

use Closure;
use PhPhD\ExceptionMapper\Blueprint\Compiler\ExceptionMappingCompiler;
use PhPhD\ExceptionMapper\Object\Blueprint\Registry\ObjectExceptionMappingBlueprintRegistry;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\CascadePropertyExceptionMappingBlueprint;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator, ContainerBuilder $container): void {
    $services = $configurator->services();

    /** @var Closure(class-string):((bool|class-string)) $hintLazy */
    $hintLazy = $container->get('phd_exception_mapper.hint_lazy_proxy');

    $services
        ->set(
            ExceptionMappingCompiler::class.'<'.ReflectionProperty::class.','.CascadePropertyExceptionMappingBlueprint::class.'>',
            CascadePropertyExceptionMappingCompiler::class,
        )->args([
            // #[Autowire(lazy: ObjectExceptionMappingBlueprintRegistry::class)]
            service(CascadePropertyExceptionMappingCompiler::class.'::$printRegistry'),
        ])
        // Better when lazy, since ObjectExceptionMappingCompiler doesn't always reach it.
        ->lazy($hintLazy(ExceptionMappingCompiler::class))
    ;

    // Making the property lazy, lest it creates a circular reference:
    $services
        ->set(
            CascadePropertyExceptionMappingCompiler::class.'::$printRegistry',
            ObjectExceptionMappingBlueprintRegistry::class,
        )->lazy()
        ->factory('current')
        ->args([[service(ObjectExceptionMappingBlueprintRegistry::class)]])
    ;
};
