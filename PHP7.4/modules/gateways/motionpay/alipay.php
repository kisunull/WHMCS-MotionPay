<?php
// alipay.php

header('Content-Type: application/json');

// Check if the request source is from an allowed origin.
$allowedDomain = 'app.vmiss.com';
if (!isset($_SERVER['HTTP_REFERER'])) { die('Forbidden'); }
$refererHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
if ($refererHost !== $allowedDomain) { die('Forbidden'); }



if (isset($_POST['pay'])) {

    $url_base = 'https://online.motionpaytech.com/onlinePayment/v1_1/pay/prePay';

    $ch = curl_init();

    // 设置cURL选项
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_URL, $url_base);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    // set header
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
    // POST
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST['params']);

    $data = curl_exec($ch);

    // response
    if ($data) {
        curl_close($ch);
        // string to json
        $data = json_decode($data, true);

        if ($data['code'] === '0') {
            $url_code = urlencode($data['content']['qrcode']);
            $response = [
                'success' => true,
                'html' => '
                    <div style="display: flex; justify-content: center; align-items: center; margin-top: 35px;">
                        <img src="modules/gateways/motionpay/qrcode.php?data=' . $url_code .'" style="width:150px;height:150px;"/>
                    </div>'
            ];
            echo json_encode($response);
        } else {
            $response = [
                'success' => false,
                'message' => $data['message']
            ];
            echo json_encode($response);
        }
    } else {
        $error = curl_errno($ch);
        curl_close($ch);
        $response = [
            'success' => false,
            'message' => "curl error, error code: $error"
        ];
        echo json_encode($response);
    }
}
