<?php

// Psalm statically scans every file loaded by the autoloader, despite conditional declaration.
// Hence, conditional declarations must be moved away from the condition files.

require __DIR__.'/SymfonyDIAbstractExtension.php';
require __DIR__.'/SymfonyHttpKernelBundleInterface.php';
require __DIR__.'/SymfonyUidInvalidArgumentException.php';
