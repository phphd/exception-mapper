<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests;

enum CascadeInclusionRule
{
    case LooselyTypedPropertyIsIncluded;
    case IterableTypedPropertyIsIncluded;
    case RelativeClassTypedPropertyIsIncluded;
    case InterfaceTypedPropertyIsIncluded;
    case NonFinalClassTypedPropertyIsIncluded;
    case FinalExceptionMappedClassTypedPropertyIsIncluded;
    case ScalarTypedPropertyIsSkipped;
    case EnumTypedPropertyIsSkipped;
    case BuiltInClassTypedPropertyIsSkipped;
    case FinalNonExceptionMappedClassTypedPropertyIsSkipped;
}
