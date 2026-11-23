<?php

namespace Symfony\Component\HttpKernel\Bundle;

use Symfony\Component\DependencyInjection\Kernel\BundleInterface as BaseBundleInterface;

/**
 * This is a snapshot of the {@see \Symfony\Component\HttpKernel\Bundle\BundleInterface} without its deprecation,
 * since extending Bundle is still needed to support older Symfony versions.
 */
interface BundleInterface extends BaseBundleInterface
{
    public function getNamespace(): string;
}
