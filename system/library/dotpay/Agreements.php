<?php

/**
*
*
* NOTICE OF LICENSE
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
*
*/

namespace dotpay;

require_once(__DIR__.'/Curl.php');

/**
 * Tool for service of Dotpay agreements and details of channel direct from Dotpay server
 */
class Agreements {
    
    /**
     * Registry class with shop environment data
     * @var Registry 
     */
    private $registry;
    
    /**
     * Target url
     * @var string
     */
    private $url;
    
    /**
     * Seller ID
     * @var int
     */
    private $id;
    
    /**
     * Order amount
     * @var float
     */
    private $amount;
    
    /**
     * Code of order currency
     * @var string
     */
    private $currency;
    
    /**
     * Language code
     * @var string
     */
    private $lang;
    
    /**
     * Constructor
     * @param Registry $registry Instance of OpenCart Registry class
     */
    public function __construct($registry) {
        $this->registry = $registry;
    }
    
    public function __get($key) {
        return $this->registry->get($key);
    }
    
    /**
     * Sets input data, needed before first request
     * @param string $url
     * @param int $id
     * @param float $amount
     * @param string $currency
     * @param string $lang
     */
    public function setInputVars($url, $id, $amount, $currency, $lang) {
        $this->url = $url;
        $this->id = $id;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->lang = $lang;
    }
    
    /**
     * Returns a specific agreements
     * @param string $what
     * @return string
     */
    protected function getAgreements($what) {
        $resultStr = '';
        $resultJson = $this->getChannels();
        if(false !== $resultJson) {
            $result = json_decode($resultJson, true);
            if (isset($result['forms']) && is_array($result['forms'])) {
                foreach ($result['forms'] as $forms) {
                    if (isset($forms['fields']) && is_array($forms['fields'])) {
                        foreach ($forms['fields'] as $forms1) {
                            if ($forms1['name'] == $what) {
                                $resultStr = $forms1['description_html'];
                            }
                        }
                    }
                }
            }
        }
        return $resultStr;
    }
    
    /**
     * Returns bylaw agreements
     * @return string
     */
    public function getByLaw() {
        $byLawAgreements = $this->getAgreements('bylaw');
        if(trim($byLawAgreements) == ''){
            $byLawAgreements = 'I accept Dotpay S.A. <a title="regulations of payments" target="_blank" href="https://ssl.dotpay.pl/files/regulamin_dotpay_sa_dokonywania_wplat_w_serwisie_dotpay_en.pdf">Regulations of Payments</a>.';
        }
        return $byLawAgreements;
    }
    
    /**
     * Returns personal data agreements
     * @return string
     */
    public function getPersonalData() {
        $personalDataAgreements = $this->getAgreements('personal_data');
        if(trim($personalDataAgreements) == ''){
            $personalDataAgreements = 'I agree to the use of my personal data by Dotpay S.A. 30-552 Kraków (Poland), Wielicka 72 for the purpose of	conducting a process of payments in accordance with applicable Polish laws (Act of 29.08.1997 for the protection of personal data, Dz. U. No 133, pos. 883, as amended). I have the right to inspect and correct my data.';
        }
        return $personalDataAgreements;
    }

    /**
     * Returns channel data, if payment channel is active for order data
     * @param int $id channel id
     * @return array|false
     */
    public function getChannelData($id) {
        $resultJson = $this->getChannels();
        if(false !== $resultJson) {
            $result = json_decode($resultJson, true);

            if(isset($result['channels']) && is_array($result['channels'])) {
                foreach($result['channels'] as $channel) {
                    if(isset($channel['id']) && $channel['id']==$id) {
                        return $channel;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Checks, if selected channel belongs to given groups
     * @param int $channelId Channel id
     * @param array $groups array of channel groups
     * @return boolean
     */
    public function isChannelInGroup($channelId, array $groups) {
        $resultJson = $this->getChannels();
        if(false !== $resultJson) {
            $result = json_decode($resultJson, true);

            if (isset($result['channels']) && is_array($result['channels'])) {
                foreach ($result['channels'] as $channel) {
                    if (isset($channel['group']) && $channel['id']==$channelId && in_array($channel['group'], $groups)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Returns string with channels data JSON
     * @return string|boolean
     */
    protected function getChannels() {
        $curlUrl = "{$this->url}payment_api/channels/";
        $curlUrl .= "?currency={$this->currency}";
        $curlUrl .= "&id={$this->id}";
        $curlUrl .= "&amount={$this->amount}";
        $curlUrl .= "&lang={$this->language->get('code')}";
        
        try {
            $curl = new Curl();
            $curl->addOption(CURLOPT_SSL_VERIFYPEER, false)
                 ->addOption(CURLOPT_HEADER, false)
                 ->addOption(CURLOPT_URL, $curlUrl)
                 ->addOption(CURLOPT_REFERER, $curlUrl)
                 ->addOption(CURLOPT_RETURNTRANSFER, true);
            $resultJson = $curl->exec();
        } catch (Exception $exc) {
            $resultJson = false;
        }
        
        if($curl) {
            $curl->close();
        }
        
        return $resultJson;
    }
}

?>