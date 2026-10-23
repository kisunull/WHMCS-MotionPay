<?php
require_once __DIR__ . '/phpqrcode.php';
if (!isset($_GET['data']) || !is_string($_GET['data']) || $_GET['data'] === '') {
    http_response_code(400);
    exit('Invalid QR code data');
}
// PHP already URL-decodes query parameters; decoding twice corrupts + and % values.
$url = $_GET['data'];
QRcode::png($url);