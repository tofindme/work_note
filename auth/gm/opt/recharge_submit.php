<?php

require_once(dirname(__FILE__)."/../title.php");
require_once(dirname(__FILE__)."/../util.php");

if(!$_GET["server_ip"])
{
	echo "没有输入服务器ip";
	return;
}
if(!$_GET["server_port"])
{
	echo "没有输入服务器端口";
	return;
}
if(!$_GET["platform_name"])
{
	echo "没有输入平台名字";
	return;
}
if(!$_GET["open_id"])
{
	echo "没有输入玩家open_id";
	return;
}

if(!$_GET["order_id"])
{
	echo "没有输入模拟订单号";
	return;
}

if(!$_GET["shop_id"])
{
	echo "没有输入商品id";
	return;
}

$server_ip = $_GET["server_ip"];
$server_port = $_GET["server_port"];
$platform_name = $_GET["platform_name"];
$open_id = $_GET["open_id"];
$order_id = $_GET["order_id"];
$shop_id = $_GET["shop_id"];

$url = "http://".$server_ip.":".$server_port."/".$platform_name."/callback?";
$url = $url."&orderId=".$order_id."&openId=".$platform_name."_".$open_id."&shopId=".$shop_id;

echo $url;
echo (https_get($url))
?>
