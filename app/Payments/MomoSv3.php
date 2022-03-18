<?php

namespace App\Payments;

use App\Models\Order;

class MomoSv3 {
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'paygate_url' => [
                'label' => 'Cổng thanh toán',
                'description' => 'Link cổng thanh toán Momo https://.......',
                'type' => 'input',
            ],
            'momo_phone' => [
                'label' => 'Số điện thoại',
                'description' => 'Số điện thoại nhận tiền',
                'type' => 'input',
            ],
            'momosv3_key' => [
                'label' => 'Token của momosv3',
                'description' => '',
                'type' => 'input',
            ]
        ];
    }

    public function pay($order)
    {

		$amount = $order['total_amount'] / 100;
		$order['momo_phone'] = $this->config['momo_phone'];
		$order['momosv3_key'] = $this->config['momosv3_key'];
		
		$cipher_method = 'aes-128-ctr';
		$enc_key = $order['momosv3_key'];
		$enc_iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher_method));
		$crypted_token = openssl_encrypt(json_encode($order), $cipher_method, $enc_key, 0, $enc_iv) . "::" . bin2hex($enc_iv);
		unset($token, $cipher_method, $enc_key, $enc_iv);
		$sig = bin2hex($crypted_token);

        return [
            'type' => 1, // 0:qrcode 1:url
            'data' => $this->config['paygate_url']."/?sig=".$sig
        ];
    }

    public function notify($params)
    {
        $token = $params['token'];
		if($this->config['momosv3_key'] != $token)
			return false;
        

        return [
            'trade_no' => $params['trade_no'],
            'callback_no' => $params['out_trade_no']
        ];
    }
	
	
}
