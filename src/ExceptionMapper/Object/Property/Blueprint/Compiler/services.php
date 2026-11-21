<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler;

use Closure;
use PhPhD\ExceptionMapper\Blueprint\Compiler\ExceptionMappingCompiler;
use PhPhD\ExceptionMapper\Object\Blueprint\Registry\ObjectExceptionMappingBlueprintRegistry;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\PropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\CatchExceptionMappingBlueprint;
use ReflectionAttribute;
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
            ExceptionMappingCompiler::class.'<'.ReflectionProperty::class.','.PropertyExceptionMappingBlueprint::class.'>',
            PropertyExceptionMappingCompiler::class,
        )->args([
            service(ExceptionMappingCompiler::class.'<'.ReflectionAttribute::class.'<'.Catch_::class.'>,'.CatchExceptionMappingBlueprint::class.'>'),
            // #[Autowire(lazy: ObjectExceptionMappingBlueprintRegistry::class)]
            service(PropertyExceptionMappingCompiler::class.'::$printRegistry'),
        ])
        // Better when lazy, since ObjectExceptionMappingCompiler doesn't always reach it.
        ->lazy($hintLazy(ExceptionMappingCompiler::class))
    ;

    // Making the property lazy, lest it creates a circular reference:
    $services
        ->set(
            PropertyExceptionMappingCompiler::class.'::$printRegistry',
            ObjectExceptionMappingBlueprintRegistry::class,
        )->lazy()
        ->factory('current')
        ->args([[service(ObjectExceptionMappingBlueprintRegistry::class)]])
    ;
};
