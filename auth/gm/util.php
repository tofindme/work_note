<?php
date_default_timezone_set('Asia/Shanghai');

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

function http_get($url, $timeout = 10)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0); // 不要http header 加快效率
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
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
function GetSortParam($param, $join = '&')
{
	ksort($param);
	$signStr = '';
	foreach ($param as $key => $value) {
		if (!empty($signStr)){
			$signStr = $signStr . $join;
		}
		$signStr = $signStr . $key . '=' . $value;
	}
	return $signStr;
}

function FormatLogStr($plateform = '61game', $type = 'callback', $str)
{
	return '[' . date('Y-m-d H:i:s') . ']' . ' [' . $plateform . '] ' . ' [' . $type . '] ' . $str;
}

function GetLogger($path, $level = 1)
{
    return Logger::getInstance($path, $level);
}
