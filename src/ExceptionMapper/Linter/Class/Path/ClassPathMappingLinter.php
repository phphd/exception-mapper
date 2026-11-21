<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Linter\Class\Path;

use PhPhD\ExceptionMapper\Linter\Class\Path\Discovery\ClassNameDiscovery;
use PhPhD\ExceptionMapper\Linter\MappingLinter;

/**
 * @internal
 *
 * @template TReport
 *
 * @implements MappingLinter<string,TReport>
 */
final class ClassPathMappingLinter implements MappingLinter
{
    /** @api */
    public function __construct(
        private readonly ClassNameDiscovery $classNameDiscovery,
        /** @var MappingLinter<class-string,TReport> */
        private readonly MappingLinter $classMappingLinter,
    ) {
    }

    /** @param iterable<string> $symbols */
    public function lint(iterable $symbols): mixed
    {
        $classNames = $this->classNameDiscovery->discover($symbols);

        return $this->classMappingLinter->lint($classNames);
    }
}
