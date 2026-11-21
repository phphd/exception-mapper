<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Linter\Port;

use PhPhD\ExceptionMapper\Linter\MappingLinter;
use PhPhD\ExceptionMapper\Linter\Report\Formatter\LintReportFormatter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services
        ->set($id = LintMappingUseCase::class, $id)
        ->public()
        ->args([
            tagged_locator(MappingLinter::class, 'id'),
            tagged_locator(LintReportFormatter::class, 'id'),
        ])
    ;
};
