<?php

//http://203.195.132.24/gm/magic.php

$priKey = 'MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBAKCKeO3hCro2Yfj5BciyFPe/7ETyt6Saj2oYta3XbpjDUDZO/H/WAViICUMT0jDqxEPkxEgkFuVLTvqozmMr+cwaLblxifvLNCYMU/Tab5vNRXSp6WvIA9rOCgjO+5iLwdSQcOXh+qsjUi5l5apZoGkE8+Y33lAoFSO5nCtCD7BtAgMBAAECgYBr4GCeSCgzNLPk30DZuYCJcjfLpOVuAEX6XxxF8otor2XI+I6HQECrQs4mer01DaxQivqyFz4jWdV6bdAxp600PrT+XMtY+7cMUKChEou9weMppfuB64CtTHxu4vmO0zCXgKdCkvRgYjrq7I7+wSEJIwMHVL1DJm0x1gk5dVCkAQJBAMxh6yw3q6DPQtrdHpCN5iubRCN3aUnFNjYCuN96ohaq1v/i3aB9yVtOkURc3OePvaOZWPZZlo3AYa7JEIStvIECQQDJFgiZ7Y+Lz5UCEeA17Rn9+PLvE1ZQJJJzNaWUGFO1MGnJcB9JS5EjOgenhzmvl8QKGOJinXpkcOpX9YhuVa3tAkB59iSkKkRcndHDURggIs0rUGgE0gkeYHTNHiq8ES4QYLoT0Il4cBdsSSIerVuVQw1jRurzdtqElDy2VH1q71IBAkB2KmyDiAaCskluHfMTvXE4vcKEm2htUBB/g1b54BHQt9JyfWDlQXLYsJEu8VgEx7p79IOUT9ZMj84mQjMaI19BAkALlhe9IjpJ+0L7rThXmCE/jX3Fz7ROdEZGORBYilVfJRUgMbr2i22fHdlvo97dB4xnG/7c+ZLyPYw+IbGmslvj';
$pubKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCginjt4Qq6NmH4+QXIshT3v+xE8rekmo9qGLWt126Yw1A2Tvx/1gFYiAlDE9Iw6sRD5MRIJBblS076qM5jK/nMGi25cYn7yzQmDFP02m+bzUV0qelryAPazgoIzvuYi8HUkHDl4fqrI1IuZeWqWaBpBPPmN95QKBUjuZwrQg+wbQIDAQAB';


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
// $uri = 'gmtype=adddiamond&uin=10050&value=20';

$uri = array(
	'gmtype' => 'adddiamond',
	'uin' => 10000014,
	'value' => 20,
	);

$str = getDataStr($uri);

$uri['sign'] = urlencode(getSign($str, $priKey)); //必需urlencode
$str = getDataStr($uri);
$url = 'http://10.10.2.39/gm/magic.php?' . $str;
echo "url is $url\n";
echo https_get($url);
