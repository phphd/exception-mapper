<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Util\Iterator;

/** @internal */
final class IsEmptyIterable
{
    /** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */
    public function __construct(
        /** @var iterable<mixed> */
        private readonly iterable $iterable,
    ) {
    }

    /** @noinspection PhpLoopNeverIteratesInspection */
    public function __invoke(): bool
    {
        /** @psalm-suppress UnusedForeachValue */
        foreach ($this->iterable as $_) {
            return false;
        }

        return true;
    }
}
