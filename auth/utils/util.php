<?php
date_default_timezone_set('Asia/Shanghai');

require_once('logger.php');

define("ERROR_0", 0);
define("ERROR_UIN", 1);//获取uin失败
define("ERROR_SIGN", 2);//签名错误
define("ERROR_CENTER", 3);//通知中心服务失败
define("ERROR_EXIST", 4);//订单已经存在且 Status:success
define("ERROR_PARAM", 5);//url参数错误
define("ERROR_THIRD", 6);//第三方返回的错误信息
define("ERROR_PAY", 7); //收到支付不成功的订单
define("ERROR_NEXIST", 8);//订单不存在
define("ERROR_GOODS", 9);//不存在的商品号
define("ERROR_STATUS", 10);//订单状态为fail




function https_post($url, $post_arr = array(), $timeout = 10)
{
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $post_arr);
	curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	$content = curl_exec($curl);
	curl_close($curl);

	return $content;
}

function https_get($url, $timeout = 10)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // 要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_HEADER, 0); // 不要http header 加快效率
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);    // https请求 不验证证书和hosts
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

//false表示16进制
function HMacSha1($data, $key, $raw = FALSE) 
{
	return hash_hmac("sha1", $data, $key, $raw);
}


//获取按字母排序后的key1=value1&key2=value2的字符串
function GetSortParam($param, $sort = TRUE, $join = '&')
{
	if ($sort){ksort($param);}
	$signStr = '';
	foreach ($param as $key => $value) {
		if (!empty($signStr)){
			$signStr = $signStr . $join;
		}
		$signStr = $signStr . $key . '=' . $value;
	}
	return $signStr;
}

function FormatLogStr($plateform = '61game', $type = 'callback', $error = 0, $str)
{
	$desc = '';
	if ($error === ERROR_0){
		$desc = 'SUCCESS';
	}elseif ($error === ERROR_UIN) {
		$desc = 'ERROR_UIN';
	}elseif ($error === ERROR_SIGN) {
		$desc = 'ERROR_SIGN';
	}elseif ($error === ERROR_CENTER) {
		$desc = 'ERROR_CENTER';
	}elseif ($error === ERROR_EXIST) {
		$desc = 'ERROR_EXIST';
	}elseif ($error === ERROR_PARAM) {
		$desc = 'ERROR_PARAM';
	}elseif ($error === ERROR_THIRD) {
		$desc = 'ERROR_THIRD';
	}elseif ($error === ERROR_PAY) {
		$desc = 'ERROR_PAY';
	}elseif ($error === ERROR_NEXIST) {
		$desc = 'ERROR_NEXIST';
	}elseif ($error === ERROR_GOODS) {
		$desc = 'ERROR_GOODS';
	}elseif ($error === ERROR_STATUS) {
		$desc = 'ERROR_STATUS';
	} else{
		$desc = 'SUCCESS';
	}
	return '[' . date('Y-m-d H:i:s') . '] ' . " [$plateform] " . "[$type] " . "[$desc] " . $str;
}

function GetLogger($path, $level = 1)
{
    return Logger::getInstance($path, $level);
}

function GetUinKey($plateform, $openid)
{
	return $plateform . '_' . $openid;
}

function GenOrderId($suffix = '_61game')
{
	return intval(date('YmdHis'). microtime() * 1000) . $suffix;
}