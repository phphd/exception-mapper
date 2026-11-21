<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper;

use Throwable;

/**
 * @api
 *
 * @template-covariant T of mixed
 */
interface ExceptionMapper
{
    /** @return ?T */
    public function map(Throwable $exception, object $object): mixed;
}
