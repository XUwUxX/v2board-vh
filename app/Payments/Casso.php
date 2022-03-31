<?php
// flypn.net #dev 1.0 Casso.vn
namespace App\Payments;

class  Casso {
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
			'paygate_url' => [
                'label' => 'Cổng thanh toán', // địa chỉ url thanh toán
                'description' => '',
                'type' => 'input',
            ],
            'BANK_ID' => [
                'label' => 'BANK_ID', //bankcode ngân hàng
                'description' => '',
                'type' => 'input',
            ],
            'ACCOUNT_NO' => [
                'label' => 'ACCOUNT_NO', // số tài khoản ngân hàng
                'description' => '',
                'type' => 'input',
            ],
            'api_Key' => [
                'label' => 'Api_Key', //key callback
                'description' => '',
                'type' => 'input',
            ],
        ];
    }

    public function pay($order)
    {
        // echo '<pre>'; debug api
        // print_r($this->config);  callback
        // die;
		$amount = $order['total_amount'] / 100;
		$trade_no = $order['trade_no'];
		$order['api_Key'] = $this->config['api_Key'];
		$order['paygate'] = "casso";


		$cipher_method = 'aes-128-ctr';
		$enc_key = $order['api_Key'];
 		$enc_iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher_method));
 		$crypted_token = openssl_encrypt(json_encode($order), $cipher_method, $enc_key, 0, $enc_iv) . "::" . bin2hex($enc_iv);
 		unset($token, $cipher_method, $enc_key, $enc_iv);

 		$sig = bin2hex($crypted_token);

        return [
            'type' => 1, // 0:qrcode 1:url
            'data' => $this->config['paygate_url']."/?paygate=casso&sig=".$sig 
        ];
    }

    public function notify($params)
    {
        $token = $params['token'];
		if($this->config['api_Key'] != $token)
			return false;


        return [
            'trade_no' => $params['trade_no'],
            'callback_no' => $params['out_trade_no']
        ];
    }


}
