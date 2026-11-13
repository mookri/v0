<?php

namespace App\Domain\Delivery\Events;

final class PaymentLifecycleEvents
{
    public const PAYMENT_INITIATED = 'payment.initiated';
    public const PAYMENT_RECEIVED = 'payment.received';
    public const PAYMENT_FAILED = 'payment.failed';
    public const PAYMENT_REFUNDED = 'payment.refunded';
}

