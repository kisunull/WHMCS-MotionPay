<?php
/**
 * MotionPay - Wechat
 * Author: Keith
 * Copyright (c) VMISS Inc. 2022
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once realpath(dirname(__FILE__)) . "/motionpay/common.php";

function motionpaywechat_MetaData()
{
    return array(
        'DisplayName' => 'MotionPay - Wechat',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}


function motionpaywechat_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'MotionPay - Wechat',
        ),
        // a text field type allows for single line text input
        'mId' => array(
            'FriendlyName' => 'mId',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your MotionPay Merchant ID here',
        ),
        // a text field type allows for single line text input
        'appId' => array(
            'FriendlyName' => 'AppId',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your MotionPay App Id here',
        ),
        // a password field type allows for masked text input
        'appSecret' => array(
            'FriendlyName' => 'AppSecret',
            'Type' => 'password',
            'Size' => '32',
            'Default' => '',
            'Description' => 'Enter your MotionPay App Secret here',
        ),
        // a text field type allows for single line text input
        'serverIP' => array(
            'FriendlyName' => 'Server IP',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'IP for Merchant Server (IP is the mandatory input for Wechat payment)',
        ),
    );
}


function motionpaywechat_link($params)
{
    // Gateway Configuration Parameters
    $mId = $params['mId'];
    $appId = $params['appId'];
    $appSecret = $params['appSecret'];
    $serverIP = $params['serverIP'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // System Parameters
    $systemUrl = $params['systemurl'];

    $url_base = 'https://online.motionpaytech.com/onlinePayment/v1_1/pay/prePay';

    $postfields = array (
        'mid' => $mId,
        'pay_channel' => 'W',
        'terminal_no' => 'WebServer',
        'goods_info' => 'VMISS-Purchase',
        'out_trade_no' => $invoiceId . MotionPay::getInvoiceStr(),
        'spbill_create_ip' => $serverIP,
        'total_fee' => round($amount * 100, 2),
        'currency_type' => $currencyCode,
        'return_url' => $systemUrl . 'modules/gateways/callback/motionpaywechat.php',
    );

    // Create Sign
    $sign = MotionPay::makeSign($postfields, $appId, $appSecret);

    // Add $sign into $postfields
    $postfields['sign'] = $sign;


    $ch = curl_init();
    // time out
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    curl_setopt($ch, CURLOPT_URL, $url_base);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    // set header
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
    // POST
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postfields));

    $data = curl_exec($ch);

    // response
    if ($data) {
        curl_close($ch);
        // string to json
        $data = json_decode($data, true);

        if ($data['code'] === '0') {
            $url_code = urlencode($data['content']['qrcode']);
            $htmlOutput = '<img src="modules/gateways/motionpay/qrcode.php?data=' . $url_code .'" style="width:150px;height:150px;"/>';
            return $htmlOutput;
        } else {
            return $data['message'];
        }
    } else {
        $error = curl_errno($ch);
        curl_close($ch);
        throw new MotionPayException("curl error，error code:$error");
    }
}