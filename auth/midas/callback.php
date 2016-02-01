<?php


require_once __DIR__ . '/lib/SnsSigCheck.php';
require(dirname(__FILE__) . "/config.php");
$config = require(dirname(__FILE__) . "/../utils/config.php");
require(dirname(__FILE__) . "/../utils/util.php");
require(dirname(__FILE__) . "/../utils/redis.php");

function getUrl($uri) {
    return strstr($uri, '?', true);
}

// 得到请求方式
$method = $_SERVER['REQUEST_METHOD'];
$url_path = getUrl($_SERVER["REQUEST_URI"]);

$param = array();
if ($method == "GET") {
    $param = $_GET;
}elseif ($method == "POST") {
    $param = $_POST;
}else {
    error_log(FormatLogStr('midas', 'callback', ERROR_PARAM,  'invalidate request method : ' . $method));
    return;
}

$appkey = $appkey . '&';
if (SnsSigCheck::verifySig($method, $url_path, $param, $appkey, $param['sig'])) {
    //获取前台附加参数uin#shopid
    $shopData = explode('*', $param['appmeta']);
    if (count($shopData) < 2) {
        error_log(FormatLogStr('midas', 'callback', ERROR_PARAM, json_encode($param)));
        die('result=FAIL&resultMsg=附加参数个数不正确');
    }

    $redis = new RedisHelper($config);
    if ($redis->CheckOrder('recharge_order', $_REQUEST['billno']))
    {
        error_log(FormatLogStr('midas', 'callback', ERROR_EXIST, json_encode($param)));
        die('{"ret":4,"msg": "订单已经存在"}');
    }

    $cache['Money'] = intval($param['amt']);
    $cache['Status'] = 'success';
    $cache['ExtOrderId'] = $param['billno'];
    $cache['PayTime'] = time();
    $cache['Time'] = time();
    $cache['Uin'] = intval($shopData[0]);
    $cache['ShopId'] = intval($shopData[1]);

    $url = $config['center'] . $centerPath . '?uin=' . $shopData[0] . '&shopId=' . $shopData[1] . '&orderId=' . $cache['ExtOrderId'];
    $res = https_get($url);
    if ($res === "success") {
        $log = GetLogger($config['logger']);
        $log->writeFile(FormatLogStr('midas', 'callback', ERROR_0, $url . json_encode($param)));
        $redis->HSet('recharge_order', $cache['ExtOrderId'], $cache);
        die('{"ret":0,"msg":"OK"}');
    }else{
        $cache['Status'] = 'fail';
        $redis->HSet('recharge_order', $cache['ExtOrderId'], $cache);
        error_log(FormatLogStr('midas', 'callback', ERROR_CENTER, $url . json_encode($param)));
        die('{"ret":4,"msg":"下发商品失败"}');
    }

}else {
    error_log(FormatLogStr('midas', 'callback', ERROR_SIGN, json_encode($param)));
    die('{"ret":4,"msg":"验证签名失败"}');
}



