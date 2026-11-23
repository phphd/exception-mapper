<?php

use Composer\InstalledVersions;

if (version_compare(InstalledVersions::getVersion('symfony/dependency-injection'), '8.0.9', '<')) {
    require __DIR__.'/conditional/SymfonyDIAbstractExtensionBefore809.php';
}
