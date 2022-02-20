<?php

class MotionPay {
    public static function getInvoiceStr($length = 12) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "-";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function makeQueryParams($postfields) {
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
        $string = sha1(utf8_encode($string));
        $result = strtoupper($string);
        return $result;
    }
}
