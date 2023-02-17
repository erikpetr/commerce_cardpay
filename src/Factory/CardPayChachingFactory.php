<?php

namespace Drupal\commerce_cardpay\Factory;

use Chaching\Chaching;

class CardPayChachingFactory {

  /**
   * Creates instance of Chaching for CardPay
   *
   * @param array $configuration
   * @param boolean $sandbox
   *
   * @return Chaching
   */
  public static function create(array $configuration, bool $sandbox): Chaching {
    $driver = Chaching::CARDPAY;
    $authorization = [
      $configuration['merchant_id'],
      $configuration['password']
    ];
    $options = ['sandbox' => $sandbox];

    return new Chaching($driver, $authorization, $options);
  }

}
