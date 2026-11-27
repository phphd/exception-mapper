<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\Tests\Stub\PullRequest;

use LengthException;
use PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\Tests\PropertyInclusionRule;
use PhPhD\ExceptionMapper\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Object\Try_;
use PhPhD\ExceptionMapper\Tests\Probe\ProbeOf;

#[Try_]
final class Branch
{
    #[ProbeOf(PropertyInclusionRule::CatchAttributedPropertyIsIncluded)]
    #[Catch_(LengthException::class)]
    public string $name;
}
