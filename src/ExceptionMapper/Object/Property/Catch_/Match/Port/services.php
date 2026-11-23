<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port;

use Closure;
use PhPhD\ExceptionMapper\ExceptionMapper;
use PhPhD\ExceptionMapper\FaultTolerant\FaultTolerantExceptionMapper;
use PhPhD\ExceptionMapper\Object\Blueprint\Registry\ObjectExceptionMappingBlueprintRegistry;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\ExceptionMatchList;
use PhPhD\ExceptionToolkit\Unwrapper\ExceptionUnwrapper;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator, ContainerBuilder $container): void {
    $services = $configurator->services();

    /** @var Closure(class-string):((bool|class-string)) $hintLazy */
    $hintLazy = $container->get('phd_exception_mapper.hint_lazy_proxy');

    $services
        ->set(ExceptionMapper::class.'<'.ExceptionMatchList::class.'>', ExceptionToMatchListMapper::class)
        ->public()
        ->args([
            service(ObjectExceptionMappingBlueprintRegistry::class),
            service('phd_exception_mapper.exception_unwrapper'),
        ])
        ->lazy($hintLazy(ExceptionMapper::class))
        ->tag(FaultTolerantExceptionMapper::class)
    ;

    $services->alias('phd_exception_mapper.exception_unwrapper', ExceptionUnwrapper::class);
};
