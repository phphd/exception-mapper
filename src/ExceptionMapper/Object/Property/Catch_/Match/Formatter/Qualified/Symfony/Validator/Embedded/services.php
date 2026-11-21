<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Symfony\Validator\Embedded;

use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator, ContainerBuilder $container): void {
    if (false === $container->getParameter('phd_exception_mapper.validator_available')) {
        return;
    }

    $services = $configurator->services();

    $services
        ->set(ExceptionMatchFormatter::class.'<'.ViolationsEmbeddedException::class.','.ConstraintViolationListInterface::class.'>', ViolationsEmbeddedExceptionFormatter::class)
        ->args([
            service('phd_exception_mapper.translator')
                ->nullOnInvalid(),
        ])
        ->tag(ExceptionMatchFormatter::class, ['id' => ViolationsEmbeddedExceptionFormatter::class])
        ->tag(ExceptionMatchFormatter::class.'<'.ViolationsEmbeddedException::class.','.ConstraintViolationListInterface::class.'>', ['id' => ViolationsEmbeddedExceptionFormatter::class])
        ->tag(ExceptionMatchFormatter::class.'<'.ValidationFailedException::class.','.ConstraintViolationListInterface::class.'>', ['id' => ViolationsEmbeddedExceptionFormatter::class])
    ;

    $services->alias(
        ExceptionMatchFormatter::class.'<'.ValidationFailedException::class.','.ConstraintViolationListInterface::class.'>',
        ExceptionMatchFormatter::class.'<'.ViolationsEmbeddedException::class.','.ConstraintViolationListInterface::class.'>',
    );
};
