<?php

namespace Drupal\commerce_cardpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_cardpay\Factory\CardPayChachingFactory;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

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

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'merchant_id' => '',
        'password' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#default_value' => $this->configuration['merchant_id'],
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $this->configuration['password'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['password'] = $values['password'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $sandbox = $this->configuration['mode'] === 'test';
    $chaching = CardPayChachingFactory::create($this->configuration, $sandbox);
    $success = FALSE;

    try {
      $payment = $chaching->response($_REQUEST);

      if ($payment->status === \Chaching\TransactionStatuses::SUCCESS) {
        $success = TRUE;
      }
      else {
        throw new PaymentGatewayException($this->t('CardPay error of @error', ['@error' => $_REQUEST['RESULTTEXT']]));
      }
    } catch (\Chaching\Exceptions\InvalidResponseException $e) {
      // General error with authentication or the response itself.
      throw new PaymentGatewayException($e->getMessage());
    }

    // @todo Add examples of request validation.
    if ($success) {
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      $payment = $payment_storage->create([
        'state' => 'authorization',
        'amount' => $order->getBalance(),
        'payment_gateway' => $this->parentEntity->id(),
        'order_id' => $order->id(),
      ]);
      $payment->save();
      $this->messenger()->addMessage($this->t('Payment was processed'));
    }
  }

  public function onCancel(OrderInterface $order, Request $request) {
  }

}
