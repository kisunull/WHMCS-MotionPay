<?php
/**
 * MotionPay - Alipay
 * Author: Keith
 * Copyright (c) VMISS Inc. 2022
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once realpath(dirname(__FILE__)) . "/motionpay/common.php";

function motionpayalipay_MetaData()
{
    return array(
        'DisplayName' => 'MotionPay - Alipay',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}


function motionpayalipay_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'MotionPay - Alipay',
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
        'transFee' => array(
            'FriendlyName' => 'TransactionFee',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '1',
            'Description' => 'Enter your transaction fee here',
        ),
    );
}


function motionpayalipay_link($params)
{
    // Gateway Configuration Parameters
    $mId = $params['mId'];
    $appId = $params['appId'];
    $appSecret = $params['appSecret'];

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
        'pay_channel' => 'A',
        'terminal_no' => 'WebServer',
        'goods_info' => 'VMISS_INC',
        'out_trade_no' => $invoiceId . MotionPay::getInvoiceStr(),
        'total_fee' => round($amount * 100, 2),
        'currency_type' => $currencyCode,
        'return_url' => $systemUrl . 'modules/gateways/callback/motionpayalipay.php',
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


function motionpayalipay_refund($params)
{
    // Gateway Configuration Parameters
    $mId = $params['mId'];
    $appId = $params['appId'];
    $appSecret = $params['appSecret'];
    $transFee = $params['transFee'];

    // Invoice Parameters
    $out_trade_no = $params['transid'];
    $amount = $params['amount'];


    // System Parameters
    $systemUrl = $params['systemurl'];

    $url_base = 'https://online.motionpaytech.com/onlinePayment/v1_1/pay/revoke';

    $postfields = array (
        'mid' => $mId,
        'out_trade_no' => $out_trade_no,
        'total_fee' => round($amount * 100, 2),
        'refund_amount' => round($amount * 100 - $transFee * 100, 2),
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
            return array(
                // 'success' if successful, otherwise 'declined', 'error' for failure
                'status' => 'success',
                // Data to be recorded in the gateway log - can be a string or array
                'rawdata' => $data,
                // Unique Transaction ID for the refund transaction
                'transid' => $data['content']['out_trade_no'],
                // Optional fee amount for the fee value refunded
                'fees' => 0,
            );
        } else {
            return array(
                // 'success' if successful, otherwise 'declined', 'error' for failure
                'status' => 'error',
                // Data to be recorded in the gateway log - can be a string or array
                'rawdata' => $data,
                // Unique Transaction ID for the refund transaction
                'transid' => $data['content']['out_trade_no'],
                // Optional fee amount for the fee value refunded
                'fees' => 0,
            );
        }
    } else {
        $error = curl_errno($ch);
        curl_close($ch);
        throw new MotionPayException("curl error，error code:$error");
    }
}