<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Value\Iterable;

use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Matcher\ExceptionMatcher;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\ExceptionReciprocal;
use PhPhD\ExceptionMapper\Object\Property\Path\PropertyPath;

/** @internal */
final class ItemOfIterableExceptionMappingNode implements ExceptionMappingNode
{
    public function __construct(
        private readonly ExceptionMappingNode $propertyNode,
        private readonly int|string $key,
        private readonly mixed $value,
        /** @var iterable<ExceptionMatcher> $matchers */
        private readonly iterable $matchers,
    ) {
    }

    public function match(ExceptionReciprocal $reciprocal): bool
    {
        foreach ($this->matchers as $matcher) {
            if ($matcher->match($reciprocal)) {
                return true;
            }
        }

        return false;
    }

    /** @codeCoverageIgnore - not used */
    public function getOwner(): ExceptionMappingNode
    {
        return $this->propertyNode;
    }

    public function getRootObject(): object
    {
        return $this->propertyNode->getRootObject();
    }

    public function getLeafObject(): object
    {
        return $this->propertyNode->getLeafObject();
    }

    public function getPropertyPath(): PropertyPath
    {
        return $this->propertyNode->getPropertyPath()
            ->at($this->key)
        ;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
