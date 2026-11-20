<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\Container;

use LogicException;
use Psr\Container\NotFoundExceptionInterface;

use function sprintf;

/** @internal */
final class ServiceNotFoundException extends LogicException implements NotFoundExceptionInterface
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Service "%s" is not found in any of the chained containers.', $id));
    }
}
