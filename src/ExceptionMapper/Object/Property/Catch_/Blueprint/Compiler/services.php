<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\Compiler;

use PhPhD\ExceptionMapper\Blueprint\Compiler\ExceptionMappingCompiler;
use PhPhD\ExceptionMapper\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\CatchExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\Compiler\Autoload\ConstantsClassLoader;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Blueprint\MatchConditionCompiler;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterClassGenericNameUnion;
use ReflectionAttribute;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Throwable;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services
        ->set(
            ExceptionMappingCompiler::class.'<'.ReflectionAttribute::class.'<'.Catch_::class.'>,'.CatchExceptionMappingBlueprint::class.'>',
            CatchExceptionMappingCompiler::class,
        )
        ->arg('$matchConditionCompiler', service(MatchConditionCompiler::class.'<'.Throwable::class.'>'))
        ->arg('$exceptionFormatterRegistry', tagged_locator(ExceptionMatchFormatter::class, 'id'))
        ->arg('$formatterClassGenericNameRegistry', tagged_locator(ExceptionMatchFormatterClassGenericNameUnion::class, 'id'))
        // #[Catch_] compilation requires mapping constants loaded
        ->configurator(service(ConstantsClassLoader::class))
    ;
};
