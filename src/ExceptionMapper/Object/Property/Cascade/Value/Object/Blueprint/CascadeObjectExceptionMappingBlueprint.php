<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Value\Object\Blueprint;

use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\Blueprint\Registry\ObjectExceptionMappingBlueprintRegistry;
use PhPhD\ExceptionMapper\Object\ObjectExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\Property\Type\Specification\MixedObjectType;

/**
 * @internal
 *
 * @implements ExceptionMappingBlueprint<ExceptionMappingNode,ObjectExceptionMappingNode>
 */
final class CascadeObjectExceptionMappingBlueprint implements ExceptionMappingBlueprint
{
    public function __construct(
        /** @var ObjectExceptionMappingBlueprintRegistry<object> */
        private readonly ObjectExceptionMappingBlueprintRegistry $printRegistry,
    ) {
    }

    public function bind(ExceptionMappingNode $embracingNode): ?ObjectExceptionMappingNode
    {
        if (!self::isObject()($object = $embracingNode->getValue())) {
            return null;
        }

        $objectPrint = $this->printRegistry->getMappingBlueprint($object::class);

        /** @noinspection PhpNullSafeOperatorCanBeUsedInspection */
        if (null === $objectPrint) {
            return null;
        }

        return $objectPrint->bind($embracingNode, $object);
    }

    public static function isObject(): MixedObjectType
    {
        static $isObject = new MixedObjectType();

        /** @phpstan-var MixedObjectType */
        return $isObject;
    }
}
