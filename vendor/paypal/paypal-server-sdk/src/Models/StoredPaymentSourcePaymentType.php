<?php

declare(strict_types=1);

/*
 * PaypalServerSdkLib
 *
 * This file was automatically generated by APIMATIC v3.0 ( https://www.apimatic.io ).
 */

namespace PaypalServerSdkLib\Models;

/**
 * Indicates the type of the stored payment_source payment.
 */
class StoredPaymentSourcePaymentType
{
    /**
     * One Time payment such as online purchase or donation. (e.g. Checkout with one-click).
     */
    public const ONE_TIME = 'ONE_TIME';

    /**
     * Payment which is part of a series of payments with fixed or variable amounts, following a fixed time
     * interval. (e.g. Subscription payments).
     */
    public const RECURRING = 'RECURRING';

    /**
     * Payment which is part of a series of payments that occur on a non-fixed schedule and/or have
     * variable amounts. (e.g. Account Topup payments).
     */
    public const UNSCHEDULED = 'UNSCHEDULED';
}
