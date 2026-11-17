<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry;

use LogicException;

use function implode;
use function sprintf;

/** @internal */
final class GenericParameterDoesNotTakeTypeException extends LogicException
{
    public function __construct(
        public string $type,
        /** @var list<string> */
        public array $allowedTypes,
    ) {
        parent::__construct(sprintf(
            'Generic parameter does not take "%s" type. Allowed types: %s.',
            $this->type,
            implode(', ', $this->allowedTypes),
        ));
    }
}
