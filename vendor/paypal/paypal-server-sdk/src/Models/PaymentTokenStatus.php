<?php

declare(strict_types=1);

/*
 * PaypalServerSdkLib
 *
 * This file was automatically generated by APIMATIC v3.0 ( https://www.apimatic.io ).
 */

namespace PaypalServerSdkLib\Models;

/**
 * The status of the payment token.
 */
class PaymentTokenStatus
{
    /**
     * A setup token is initialized with minimal information, more data must be added to the setup-token to
     * be vaulted
     */
    public const CREATED = 'CREATED';

    /**
     * A contingecy on payer approval is required before the payment method can be saved.
     */
    public const PAYER_ACTION_REQUIRED = 'PAYER_ACTION_REQUIRED';

    /**
     * Setup token is ready to be vaulted. If a buyer approval contigency was returned, it is has been
     * approved.
     */
    public const APPROVED = 'APPROVED';

    /**
     * The payment token has been vaulted.
     */
    public const VAULTED = 'VAULTED';

    /**
     * A vaulted payment method token has been tokenized for short term (one time) use.
     */
    public const TOKENIZED = 'TOKENIZED';
}
