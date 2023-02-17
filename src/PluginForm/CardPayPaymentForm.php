<?php

namespace Drupal\commerce_cardpay\PluginForm;

use Chaching\Currencies;
use Drupal\commerce_cardpay\Factory\CardPayChachingFactory;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class CardPayPaymentForm extends PaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $configuration = $payment_gateway_plugin->getConfiguration();

    // TODO: Adjust to fit CardPay
    $order_data = [
      'variable_symbol' => $this->generateVariableSymbol($payment->getOrderId()),
      'MERORDERNUM' => $payment->getOrderId(),
      'amount' => $payment->getAmount()->getNumber(),
      'description' => 'test order',
      'constant_symbol' => '0308',
      'return_email' => '...',
      'callback' => $form['#return_url'],
    ];
    $is_test = $configuration['mode'] === 'test';
    $redirect_url = $this->requestFormData($configuration, $order_data, $is_test);

    $data = [
      'return' => $form['#return_url'],
      'cancel' => $form['#cancel_url'],
      'total' => $payment->getAmount()->getNumber(),
    ];

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data);
  }

  private function requestFormData(array $configuration, array $order_data, bool $sandbox) {
    $chaching = CardPayChachingFactory::create($configuration, $sandbox);

    // TODO: Adjust to fit CardPay
    $payment = $chaching->request([
      'currency' => Currencies::EUR,
      'variable_symbol' => $order_data['variable_symbol'],
      'amount' => $order_data['amount'],
      'description' => $order_data['description'],
      'constant_symbol' => $order_data['constant_symbol'],
      'return_email' => $order_data['return_email'],
      'callback' => $order_data['callback'],
    ]);

    $redirect_url = NULL;
    try {
      $redirect_url = $payment->process($auto_redirect = FALSE);
    } catch (\Chaching\Exceptions\InvalidOptionsException $e) {
      // Missing or incorrect value of some configuration option.
    } catch (\Chaching\Exceptions\InvalidRequestException $e) {
      // General error with authentication or the request itself.
    }

    return $redirect_url;
  }

  private function generateVariableSymbol(int $variable_symbol) {
    return substr(time(), -3) . sprintf('%08d', $variable_symbol);
  }
}
