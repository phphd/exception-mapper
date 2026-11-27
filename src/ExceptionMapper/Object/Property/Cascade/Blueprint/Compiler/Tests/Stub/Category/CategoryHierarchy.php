<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\Stub\Category;

use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\CascadeInclusionRule;
use PhPhD\ExceptionMapper\Tests\Probe\ProbeOf;

/** Declared in a trait, so self and parent types stay unresolved at runtime */
trait CategoryHierarchy
{
    #[ProbeOf(CascadeInclusionRule::RelativeClassTypedPropertyIsIncluded)]
    private ?self $parentSubcategory;

    #[ProbeOf(CascadeInclusionRule::RelativeClassTypedPropertyIsIncluded)]
    private ?parent $rootCategory;
}
