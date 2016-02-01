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
if(!$_GET["uin"])
{
	echo "没有输入玩家uin";
	return;
}

if(!$_GET["value"])
{
	echo "没有数量";
	return;
}

$server_ip = $_GET["server_ip"];
$server_port = $_GET["server_port"];
$uin = $_GET["uin"];
$value = $_GET["value"];

$url = "http://".$server_ip.":".$server_port."/gm?";
$url = $url."gmtype=addscore&uin=".$uin."&score=".$value;

echo (https_get($url))
?>
