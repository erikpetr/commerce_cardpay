<?php

namespace Drupal\commerce_cardpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "cardpay",
 *   label = "CardPay",
 *   display_label = "CardPay",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_cardpay\PluginForm\CardPayPaymentForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa",
 *   },
 * )
 */
class CardPayPayment extends OffsitePaymentGatewayBase {

}
