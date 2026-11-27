<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Blueprint\Matcher;

use Iterator;
use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Matcher\ExceptionMatcherIteratorAggregate;
use Webmozart\Assert\Assert;

/**
 * @internal
 *
 * @template TEmbracingNode of ExceptionMappingNode
 */
final class LateBindingExceptionMatcherIteratorAggregate implements ExceptionMatcherIteratorAggregate
{
    /** @var ?TEmbracingNode */
    private ?ExceptionMappingNode $embracingNode = null;

    public function __construct(
        /** @var iterable<ExceptionMappingBlueprint<TEmbracingNode,ExceptionMappingNode>> */
        private readonly iterable $prints,
    ) {
    }

    /** @param TEmbracingNode $embracingNode */
    public function attachNode(ExceptionMappingNode $embracingNode): void
    {
        Assert::null($this->embracingNode);

        $this->embracingNode = $embracingNode;
    }

    public function getIterator(): Iterator
    {
        Assert::notNull($this->embracingNode);

        foreach ($this->prints as $print) {
            if (null !== $node = $print->bind($this->embracingNode)) {
                yield $node;
            }
        }
    }
}
