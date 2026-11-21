<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Catch_\Match;

use Countable;
use Generator;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use Throwable;

use function count;

/** @api */
final class ExceptionMatchList implements Countable
{
    public function __construct(
        /** @var list<ExceptionMatch<Throwable>> */
        private readonly array $exceptionMatches,
    ) {
    }

    /**
     * @template TResult
     *
     * @param ExceptionMatchFormatter<Throwable,TResult> $formatter
     *
     * @return Generator<TResult>
     */
    public function format(ExceptionMatchFormatter $formatter): Generator
    {
        foreach ($this->exceptionMatches as $match) {
            yield $formatter->format($match);
        }
    }

    /**
     * @internal
     * @codeCoverageIgnore
     *
     * @return list<ExceptionMatch<Throwable>>
     */
    public function toArray(): array
    {
        return $this->exceptionMatches;
    }

    public function count(): int
    {
        return count($this->exceptionMatches);
    }
}
