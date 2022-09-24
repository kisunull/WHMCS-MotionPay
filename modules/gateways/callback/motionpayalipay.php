<?php
/**
 * MotionPay - Alipay
 * Author: Keith
 * Copyright (c) VMISS Inc. 2022
 */

// Require libraries needed for gateway module functions.
@require_once ("../../../init.php");
@require_once ("../../../includes/gatewayfunctions.php");
@require_once ("../../../includes/invoicefunctions.php");
@require_once ("../motionpay/common.php");



// Get the JSON contents
$json = file_get_contents('php://input');
// decode the json data
$data = json_decode($json, true);

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Gateway Configuration Parameters
$appId = $gatewayParams['appId'];
$appSecret = $gatewayParams['appSecret'];
$pay_result = $data["pay_result"];
$invoiceId = $data["out_trade_no"];
$amount = $data["settlement_amount"];
$transactionId = $data["out_trade_no"];
$sign = $data["sign"];
// $currency_type = $data["currency_type"];
// $mid = $data["mid"];
// $pay_channel = $data["pay_channel"];
// $third_order_no = $data["third_order_no"];
// $user_identify = $data["user_identify"];
// $exchange_rate = $data["exchange_rate"];
$fee = 0;

// Verify Payment result
if ($pay_result!=='SUCCESS') die("Payment Failed");

// Create Sign
$postfields = $data;
unset($postfields['sign']);
$_sign = MotionPay::makeSign($postfields, $appId, $appSecret);


// Verify sign
if ($sign!==$_sign) die("Invalid Sign");

// Trim string after "-" in the invoiceId: 10-wdgbsfw1uy76 => 10
$invoiceId = substr($invoiceId, 0, strpos($invoiceId, '-'));

// Checks invoice ID is a valid invoice number.
checkCbInvoiceID($invoiceId, $gatewayModuleName);

// Performs a check for any existing transactions with the same given transaction number.
checkCbTransID($transactionId);

// to actual amount
$amount = round($amount / 100, 2);

addInvoicePayment($invoiceId, $transactionId, $amount, $fee, $gatewayModuleName);
logTransaction('MotionPay - Alipay', $json, "Successful Paid: " . $amount);


// return MotionPay
$result = array('code' => '0', 'message' => 'success');
echo json_encode($result);

?>