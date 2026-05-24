<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

class MotionPay {
    public static function getInvoiceStr($length = 12) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "-";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public static function makeQueryParams($postfields) {
        $buff = "";
        foreach ($postfields as $k => $v) {
            if ($v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    public static function makeSignParams($postfields, $appId, $appSecret) {
        $buff = "";
        ksort($postfields);
        $buff .= self::makeQueryParams($postfields);
        $buff .= '&appid=' . $appId . '&appsecret=' . $appSecret;
        return $buff;
    }

    public static function makeSign($postfields, $appId, $appSecret) {
        $string = self::makeSignParams($postfields, $appId, $appSecret);
        $string = sha1(($string),TRUE);
        $string = bin2hex($string);
        $result = strtoupper($string);
        return $result;
    }

    public static function isTransactionProcessed($transactionId) {
        if (empty($transactionId)) {
            return false;
        }

        return Capsule::table('tblaccounts')->where('transid', $transactionId)->exists();
    }

    public static function respondCallbackSuccess() {
        header('Content-type: application/json');
        echo json_encode(array('code' => '0', 'message' => 'success'));
        exit;
    }

    public static function acquireDbLock($transactionId) {
        $lockName = 'motionpay_' . md5($transactionId);

        $result = Capsule::selectOne('SELECT GET_LOCK(?, ?) AS locked', [
            $lockName,
            5,
        ]);

        return ($result && intval($result->locked) === 1) ? $lockName : false;
    }

    public static function releaseDbLock($lockName) {
        if ($lockName) {
            Capsule::selectOne('SELECT RELEASE_LOCK(?) AS released', [
                $lockName,
            ]);
        }
    }
}
