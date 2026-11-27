<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Value\Iterable\Blueprint;

use Generator;
use PhPhD\ExceptionMapper\Blueprint\Matcher\LateBindingExceptionMatcherIteratorAggregate;
use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Matcher\ExceptionMatcherIteratorAggregate;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Value\Blueprint\CascadeValueExceptionMatcherBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Type\Specification\MixedIterableType;

use function is_int;
use function is_string;

/** @internal */
final class CascadeIterableExceptionMatcherIteratorBlueprint
{
    public function __construct(
        private readonly CascadeValueExceptionMatcherBlueprint $cascadeValuePrint,
    ) {
    }

    public function bind(ExceptionMappingNode $embracingNode): ?ExceptionMatcherIteratorAggregate
    {
        $value = $embracingNode->getValue();

        if ([] === $value || !self::isIterable()($value)) {
            return null;
        }

        $iterableMatcher = new LateBindingExceptionMatcherIteratorAggregate($this->iterableItemBlueprints($value));

        $iterableMatcher->attachNode($embracingNode);

        return $iterableMatcher;
    }

    /**
     * @param iterable<mixed,mixed> $iterable
     *
     * @return Generator<int,ItemOfIterableExceptionMappingBlueprint>
     */
    private function iterableItemBlueprints(iterable $iterable): Generator
    {
        foreach ($iterable as $key => $item) {
            if (!is_int($key) && !is_string($key)) {
                continue;
            }

            yield new ItemOfIterableExceptionMappingBlueprint($this->cascadeValuePrint, $key, $item);
        }
    }

    /** Iterable might contain #[Try_]-attributed objects */
    public static function isIterable(): MixedIterableType
    {
        static $isIterable = new MixedIterableType();

        /** @phpstan-var MixedIterableType */
        return $isIterable;
    }
}
