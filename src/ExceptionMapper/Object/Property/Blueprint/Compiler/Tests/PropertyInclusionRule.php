<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\Tests;

enum PropertyInclusionRule
{
    case CatchAttributedPropertyIsIncluded;
    case NonCatchAttributedPropertyIsSkipped;
}
