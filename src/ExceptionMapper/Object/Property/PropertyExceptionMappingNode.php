<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property;

use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Matcher\ExceptionMatcher;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\ExceptionReciprocal;
use PhPhD\ExceptionMapper\Object\Property\Path\PropertyPath;

/** @internal */
final class PropertyExceptionMappingNode implements ExceptionMappingNode
{
    public function __construct(
        private readonly ExceptionMappingNode $objectNode,
        private readonly string $name,
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
        return $this->objectNode;
    }

    public function getRootObject(): object
    {
        return $this->objectNode->getRootObject();
    }

    public function getLeafObject(): object
    {
        return $this->objectNode->getLeafObject();
    }

    public function getPropertyPath(): PropertyPath
    {
        return $this->objectNode->getPropertyPath()
            ->with($this->name)
        ;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
