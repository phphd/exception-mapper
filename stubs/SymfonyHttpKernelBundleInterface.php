<?php

namespace Symfony\Component\HttpKernel\Bundle;

use Composer\InstalledVersions;

if (version_compare(InstalledVersions::getVersion('symfony/http-kernel'), '8.1', '>=')) {
    require __DIR__.'/conditional/SymfonyHttpKernelBundleInterfaceSince81.php';
} else {
    // Fix psalm on old releases by adding the new BaseBundleInterface symbol.
    class_alias(
        BundleInterface::class,
        Symfony\Component\DependencyInjection\Kernel\BundleInterface::class,
    );
}
