<?php

/**
 * NOTICE OF LICENSE.
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to tech@dotpay.pl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade OpenCart to newer
 * versions in the future. If you wish to customize OpenCart for your
 * needs please refer to http://www.dotpay.pl for more information.
 *
 *  @author    Dotpay Team <tech@dotpay.pl>
 *  @copyright Dotpay
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace dotpay;

require_once __DIR__.'/Curl.php';

/**
 * Provides the functionality of seller API.
 */
class SellerApi
{
    /**
     * Name of plugin.
     */
    const PLUGIN_NAME = 'dotpay_new';

    /**
     * Registry class with shop environment data.
     *
     * @var Registry
     */
    private $registry;

    /**
     * Prepare object for using.
     *
     * @param Registry $registry Registry object with environment data
     */
    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    /**
     * Returns value of property, using Registry object.
     *
     * @param string $key Name of property
     *
     * @return type
     */
    public function __get($key)
    {
        return $this->registry->get($key);
    }

    /**
     * Return infos about credit card.
     *
     * @param string $username
     * @param string $password
     * @param string $number
     *
     * @return \stdClass
     */
    public function getCreditCardInfo($username, $password, $number)
    {
        $payment = $this->getPaymentByNumber($username, $password, $number);
        if ($payment->payment_method->channel_id != 248) {
            return null;
        }

        return $payment->payment_method->credit_card;
    }

    /**
     * Check, if username and password are right.
     *
     * @param string $username Username of user
     * @param string $password Password of user
     * @param string $version  Version of used Dotpay Api
     *
     * @return bool
     */
    public function isAccountRight($username, $password, $version)
    {
        if ($version == 'legacy') {
            return true;
        }
        if (empty($username) && empty($password)) {
            return true;
        }
        $url = $this->config->get('payment_'.self::PLUGIN_NAME.'_target_seller_url').$this->getDotPaymentApi().'payments/';
        $curl = new Curl();
        $curl->addOption(CURLOPT_URL, $url)
             ->addOption(CURLOPT_USERPWD, $username.':'.$password);
        $this->setCurlOption($curl);
        $curl->exec();
        $info = $curl->getInfo();

        return $info['http_code'] >= 200 && $info['http_code'] < 300;
    }

    /**
     * Return infos about payment.
     *
     * @param string $username
     * @param string $password
     * @param string $number
     *
     * @return \stdClass
     */
    public function getPaymentByNumber($username, $password, $number)
    {
        $url = $this->config->get('payment_'.self::PLUGIN_NAME.'_target_seller_url').$this->getDotPaymentApi()."payments/$number/";
        $curl = new Curl();
        $curl->addOption(CURLOPT_URL, $url)
             ->addOption(CURLOPT_USERPWD, $username.':'.$password);
        $this->setCurlOption($curl);
        $response = json_decode($curl->exec());
var_dump($response);
        return $response;
    }

    /**
     * Return ifnos about payment.
     *
     * @param string $username
     * @param string $password
     * @param int    $orderId
     *
     * @return \stdClass
     */
    public function getPaymentByOrderId($username, $password, $orderId)
    {
        $url = $this->config->get('payment_'.self::PLUGIN_NAME.'_target_seller_url').$this->getDotPaymentApi().'payments/?control='.$orderId;
        $curl = new Curl();
        $curl->addOption(CURLOPT_URL, $url)
             ->addOption(CURLOPT_USERPWD, $username.':'.$password);
        $this->setCurlOption($curl);
        $response = json_decode($curl->exec());

        return $response->results;
    }

    /**
     * Return path for payment API.
     *
     * @return string
     */
    private function getDotPaymentApi()
    {
        return 'api/';
    }

    /**
     * Set option for cUrl and return cUrl resource.
     *
     * @param resource $curl
     */
    private function setCurlOption($curl)
    {
        $curl->addOption(CURLOPT_SSL_VERIFYPEER, true)
             ->addOption(CURLOPT_SSL_VERIFYHOST, 2)
             ->addOption(CURLOPT_RETURNTRANSFER, 1)
             ->addOption(CURLOPT_TIMEOUT, 100)
             ->addOption(CURLOPT_CUSTOMREQUEST, 'GET');
    }
}
