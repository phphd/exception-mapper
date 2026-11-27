<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\Stub\Order\Shipping;

use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\CascadeInclusionRule;
use PhPhD\ExceptionMapper\Object\Try_;
use PhPhD\ExceptionMapper\Tests\Probe\ProbeOf;

#[Try_]
final class ShippingAddress
{
    #[ProbeOf(CascadeInclusionRule::ScalarTypedPropertyIsSkipped)]
    public string $postalCode;

    /** Neighbour's address, should the courier find no one home */
    #[ProbeOf(CascadeInclusionRule::FinalExceptionMappedClassTypedPropertyIsIncluded, 'self-referencing')]
    public ?ShippingAddress $fallbackAddress = null;
}
