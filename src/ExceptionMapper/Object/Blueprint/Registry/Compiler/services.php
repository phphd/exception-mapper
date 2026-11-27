<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Blueprint\Registry\Compiler;

use PhPhD\ExceptionMapper\Blueprint\Compiler\ExceptionMappingCompiler;
use PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension;
use PhPhD\ExceptionMapper\Object\Blueprint\ObjectExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\PropertyExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\CascadePropertyExceptionMappingBlueprint;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator, ContainerBuilder $container): void {
    $services = $configurator->services();

    $compiler = $services
        ->set(
            ExceptionMappingCompiler::class.'<'.ReflectionClass::class.','.ObjectExceptionMappingBlueprint::class.'>',
            ObjectExceptionMappingCompiler::class,
        )
        ->args([
            service(ExceptionMappingCompiler::class.'<'.ReflectionProperty::class.','.PropertyExceptionMappingBlueprint::class.'>'),
            service(ExceptionMappingCompiler::class.'<'.ReflectionProperty::class.','.CascadePropertyExceptionMappingBlueprint::class.'>'),
        ])
    ;

    if ($container->hasParameter('kernel.debug') && false === $container->getParameter('kernel.debug')) {
        $compiler
            ->call('reportingTo', [service('logger')->ignoreOnInvalid()], returnsClone: true)
            ->tag('monolog.logger', ['channel' => PhdExceptionMapperExtension::LOGGER_CHANNEL])
        ;
    }
};
