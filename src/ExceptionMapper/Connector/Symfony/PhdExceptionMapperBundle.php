<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Connector\Symfony;

use PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\Compiler\Autoload\ConstantsAutoloadingCompilerPass;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\AutoTag\ExceptionMatchFormatterTagAutoconfigurationCompilerPass;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\Materialize\ExceptionMatchFormatterTagMaterializationCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/** @api */
final class PhdExceptionMapperBundle extends Bundle
{
    /** @override */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new ConstantsAutoloadingCompilerPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            ConstantsAutoloadingCompilerPass::PRIORITY,
        );
        $container->addCompilerPass(
            new ExceptionMatchFormatterTagAutoconfigurationCompilerPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            ExceptionMatchFormatterTagAutoconfigurationCompilerPass::PRIORITY,
        );
        $container->addCompilerPass(
            new ExceptionMatchFormatterTagMaterializationCompilerPass(),
            PassConfig::TYPE_OPTIMIZE,
            ExceptionMatchFormatterTagMaterializationCompilerPass::PRIORITY,
        );
    }

    /** @override */
    protected function createContainerExtension(): PhdExceptionMapperExtension
    {
        return new PhdExceptionMapperExtension(generatedProxiesAllowed: true);
    }
}
