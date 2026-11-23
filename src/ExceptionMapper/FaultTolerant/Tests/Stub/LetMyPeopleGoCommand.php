<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\FaultTolerant\Tests\Stub;

use LogicException;
use PhPhD\ExceptionMapper\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Object\Try_;

#[Try_]
final class LetMyPeopleGoCommand
{
    public const MAPPING_FAILURE_MESSAGE = 'The match condition has failed.';

    #[Catch_(PharaohRefusedException::class, if: [self::class, 'failToMatch'])]
    private string $people = 'Israel';

    /** @api */
    public function failToMatch(): bool
    {
        throw new LogicException(self::MAPPING_FAILURE_MESSAGE);
    }
}
