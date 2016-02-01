<?php

require __DIR__ . "/magic_cfg.php";
require(dirname(__FILE__) . "/../utils/util.php");

function getSign($data, $priKey) {
	$pem = chunk_split($priKey,64,"\n");
	$pem = "-----BEGIN PRIVATE KEY-----\n".$pem."-----END PRIVATE KEY-----";
	$priKey = openssl_pkey_get_private ( $pem );

	$res = openssl_sign ( $data, $encrypted, $priKey );
	if ($res) {
		return base64_encode($encrypted);
	}else {
		return null;
	}
}

function getDataStr($data){
	ksort($data);
	$query_string = array();
    foreach ($data as $key => $val ) 
    { 
        array_push($query_string, $key . '=' . $val);
    }   
    $query_string = join('&', $query_string);
    return $query_string;
}

function verify($data, $srcSign, $pubKey) {
	$pem = chunk_split($pubKey,64,"\n");
	$pem = "-----BEGIN PUBLIC KEY-----\n".$pem."-----END PUBLIC KEY-----";
	$pubKey = openssl_pkey_get_public ( $pem );
	if (openssl_verify($data, base64_decode($srcSign), $pubKey) == 1 )
		return true;
	return false;
}

$method = $_SERVER['REQUEST_METHOD'];
$data = array();
if ($method == "GET") {
	$data = $_GET;
}elseif ($method == "POST") {
	$data = $_POST;
}else{
	error_log(FormatLogStr('gm', 'magic.php', ERROR_0, 'invalidate request method : ' . $method));
	die('false');
}

if (empty($data['sign'])) {
	//没有发现签名数据
	error_log(FormatLogStr('gm', 'magic.php', ERROR_0, 'not found sign param with param ' . json_encode($data)));
	die('false');
}

//得到请求中的sign
$sign = $data['sign'];
//去除sign字段
unset($data['sign']);
//得到请求参数
$str = getDataStr($data);

if (verify($str, $sign, $pubKey)) {
	echo https_get($gmaddr . '?' . $str);
}else{
	error_log(FormatLogStr('gm', 'magic.php', ERROR_0, 'verify sign failed with param ' . $str . " with sign : $sign"));
	echo 'false';
}
