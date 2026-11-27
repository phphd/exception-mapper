<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\Stub\Order;

use DateTimeImmutable;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\CascadeInclusionRule;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\Stub\Order\Customer\Customer;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\Stub\Order\Line\OrderLines;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\Stub\Order\Payment\PaymentMethod;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\Stub\Order\Pricing\Discount;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\Stub\Order\Pricing\LoyaltyReward;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\Stub\Order\Shipping\ShippingAddress;
use PhPhD\ExceptionMapper\Object\Property\Cascade\Blueprint\Compiler\Tests\Stub\Order\Shipping\ShippingMethod;
use PhPhD\ExceptionMapper\Tests\Probe\ProbeOf;
use Stringable;
use Traversable;

final class PlaceOrderCommand
{
    #[ProbeOf(CascadeInclusionRule::NonFinalClassTypedPropertyIsIncluded, 'concrete class')]
    private Customer $customer;

    /** @psalm-suppress MissingPropertyType Left untyped, as legacy properties are */
    #[ProbeOf(CascadeInclusionRule::LooselyTypedPropertyIsIncluded)] // @phpstan-ignore typeCoverage.propertyTypeCoverage
    private $customerNote; // @phpstan-ignore missingType.property

    #[ProbeOf(CascadeInclusionRule::IterableTypedPropertyIsIncluded)]
    private OrderLines $lines;

    #[ProbeOf(CascadeInclusionRule::NonFinalClassTypedPropertyIsIncluded, 'abstract class')]
    private Discount $discount;

    /** @var list<string> */
    #[ProbeOf(CascadeInclusionRule::IterableTypedPropertyIsIncluded)]
    private array $couponCodes;

    /** @var iterable<string> */
    #[ProbeOf(CascadeInclusionRule::IterableTypedPropertyIsIncluded)]
    private iterable $giftCardCodes;

    /** @var Traversable<int,LoyaltyReward> */
    #[ProbeOf(CascadeInclusionRule::IterableTypedPropertyIsIncluded)]
    private Traversable $loyaltyRewards;

    #[ProbeOf(CascadeInclusionRule::ScalarTypedPropertyIsSkipped)]
    private int $loyaltyPointsToSpend;

    #[ProbeOf(CascadeInclusionRule::InterfaceTypedPropertyIsIncluded)]
    private PaymentMethod $paymentMethod;

    #[ProbeOf(CascadeInclusionRule::LooselyTypedPropertyIsIncluded)]
    private object $gatewayPayload;

    #[ProbeOf(CascadeInclusionRule::FinalExceptionMappedClassTypedPropertyIsIncluded)]
    private ShippingAddress $shippingAddress;

    #[ProbeOf(CascadeInclusionRule::EnumTypedPropertyIsSkipped)]
    private ShippingMethod $shippingMethod;

    #[ProbeOf(CascadeInclusionRule::BuiltInClassTypedPropertyIsSkipped, 'class')]
    private DateTimeImmutable $requestedDeliveryDate;

    #[ProbeOf(CascadeInclusionRule::BuiltInClassTypedPropertyIsSkipped, 'interface')]
    private Stringable $recipientPhone;

    #[ProbeOf(CascadeInclusionRule::FinalNonExceptionMappedClassTypedPropertyIsSkipped)]
    private Money $courierTip;

    #[ProbeOf(CascadeInclusionRule::LooselyTypedPropertyIsIncluded)]
    private mixed $metadata;
}
