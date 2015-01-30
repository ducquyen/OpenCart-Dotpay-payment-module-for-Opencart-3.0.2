<?php

class ControllerPaymentDotpay extends Controller {

    const OPERATION_TYPE_PAYMENT = 'payment';
    const OPERATION_TYPE_REFUND = 'refund';
    
    private $error = array();
    
    public function index() {
        
        $this->load->model('checkout/order');
        $this->load->model('setting/setting');
        $this->load->library('encryption');
        $this->load->language('payment/dotpay');
        
        
        $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);  
                
        $data['text_button_confirm'] = $this->language->get('text_button_confirm');        
        
        $data['order_id'] = $order['order_id'];        
        $data['dotpay'] = $this->geParams($order);
       
        $data['action'] = $this->config->get('dotpay_request_url');
        $data['method'] = $this->config->get('dotpay_request_method');
               
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/dotpay.tpl')) {
            return $this->load->view($this->config->get('config_template') . '/template/payment/dotpay.tpl', $data);
        } else {
            return $this->load->view('default/template/payment/dotpay.tpl', $data);
        }
    }
    
    private function geParams($order){
        
        $data = array();        
        //requried
        $this->load->model('setting/setting');
        $data['id']=$this->config->get('dotpay_id');               
        $data['currency']=$this->config->get('dotpay_currency');
        $data['amount']=number_format($this->currency->format($order['total'],$data['currency'], $order['currency_value'], FALSE), 2, '.', '');
        $data['lang'] = $this->session->data['language'];
        $data['description'] = $order['comment'];               
        $data['p_info'] = $this->config->get('config_name');
        $data['p_email'] = $this->config->get('config_email');       
        $data['control'] = $order['order_id'];
        $data['api_version'] = $this->config->get('dotpay_api_version');     
        
        //optional
//        $data['URL'] = HTTPS_SERVER . $this->config->get('dotpay_URL'); 
//        $data['URLC'] = HTTPS_SERVER . $this->config->get('dotpay_URLC'); 
        $data['URL'] = 'http://3f430544.ngrok.com/' . $this->config->get('dotpay_URL'); 
        $data['URLC'] = 'http://3f430544.ngrok.com/' . $this->config->get('dotpay_URLC'); 
        $data['type'] = $this->config->get('dotpay_type');
        
        
        return $data;
    }
  
    public function callback(){
        error_log("DOTPAY-CALLBACK: " );
        
        $this->document->setTitle($this->language->get('heading_title'));

		
        $this->language->load('payment/dotpay');
        
        $data['heading_title'] = $this->language->get('heading_title');       
      
        $data['text_response'] = $this->language->get('text_response');

        if (isset($this->request->get['status']) || $this->request->post['status'] == 'OK')
        {
//            $data['text_success'] = $this->language->get('text_success');
//            $data['text_success_wait'] = sprintf($this->language->get('text_success_wait'), HTTPS_SERVER . 'index.php?route=checkout/success');
            $data['button_continue'] = HTTPS_SERVER . 'index.php?route=checkout/success';
            echo 'OK';
            
        } else
        {
            echo 'FAIL';
//            $data['text_failure'] = $this->language->get('text_failure');
//            $failureAction = $this->request->get['route'] != 'checkout/guest_step_3' ? 'payment' : 'guest_step_2';
//            $data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), HTTPS_SERVER . 'index.php?route=checkout/' . $failureAction);
            $data['button_continue'] = HTTPS_SERVER . 'index.php?route=checkout/cart';

            
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
       
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/dotpay_callback.tpl')) {
			$this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/payment/dotpay_callback.tpl', $data));
		} else {
			$this->response->setOutput($this->load->view('default/template/payment/dotpay_callback.tpl', $data));
		}
    }

    public function confirmation() {
        
        
        $this->request->post = $this->request->get;
        
        foreach ($_POST as $key=>$value){
            error_log("DOTPAY-POST: ".$key . ":" . $value );
        }
        
        $this->load->model('checkout/order');
        $this->load->language('payment/dotpay');
        
        
        $orderID = $this->request->post['control'];             
        $order = $this->model_checkout_order->getOrder($orderID);
        $order_status = $order['order_status_id'];
        $order_status_completed =$this->config->get('dotpay_status_completed');
        $order_status_rejected = $this->config->get('dotpay_status_rejected');
        
        if (!$order)
            throw new Exception('Unknown order id.');
        
        if ($this->request->post['operation_type'] == self::OPERATION_TYPE_PAYMENT)
        {
            if ($this->isValid($this->request->post)){                         
                if ($order_status != $order_status_completed)
                {       
                    $message = date('H:i:s ') . $this->language->get('info_dotpay_operation_number') . $this->request->post['operation_number'];                   
                    $this->model_checkout_order->addOrderHistory($orderID, $order_status_completed, $message, TRUE);                    
                }
            }else {

                if ($this->error['error_signature']) {   
                    
                    $message = date('H:i:s ') . $this->language->get('error_signature');
                    $this->model_checkout_order->addOrderHistory($orderID, $order_status_rejected, $message, TRUE);                
                }
                
                return;
            }            
            
        } else if ($this->request->post['operation_type'] == self::OPERATION_TYPE_REFUND)
        {
            
        }      
       
        echo 'OK';
        
    }
    
    private function isValid($params){
                
        if (!$this->calculateSign($params)){
            $this->error['error_signature'] = 1;
        }
       
       
        if ($_SERVER["REMOTE_ADDR"] != $this->config->get('dotpay_ip')){
//            $this->error['error_address_ip'] = 1;
        }
            
        return (!$this->error ? true : false);
        
    }
    
    private function calculateSign($params){
        
        $PIN = $this->config->get('dotpay_pin');
        $sign = $PIN . 
                $params['id'] .  
                $params['operation_number'] .  
                $params['operation_type'] .  
                $params['operation_status'] .  
                $params['operation_amount'] . 
                $params['operation_currency'] .  
                $params['operation_original_amount'] .  
                $params['operation_original_currency'] .  
                $params['operation_datetime'] .  
                (isset($params['operation_related_number']) ? $params['operation_related_number'] : '' ) .  
                $params['control'] .
                $params['description'] .
                $params['email'] .
                $params['p_info'] .
                $params['p_email'] .
                $params['channel'] .
                (isset($params['channel_country']) ? $params['channel_country'] : '' ) .  
                (isset($params['geoip_country']) ? $params['geoip_country'] : '' );
        
        
        if (hash('sha256', $sign) == $params['signature']){           
            return true;
        }
        
        return false;
    }

}

?>