
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
if(!$_GET["level"])
{
	echo "没有输入等级";
	return;
}
if(!$_GET["exp"])
{
	echo "没有输入经验";
	return;
}

$server_ip = $_GET["server_ip"];
$server_port = $_GET["server_port"];
$uin = $_GET["uin"];
$level = $_GET["level"];
$exp = $_GET["exp"];

$url = "http://".$server_ip.":".$server_port."/gm?";
$url = $url."gmtype=changelevel&uin=".$uin."&level=".$level."&exp=".$exp;

echo (https_get($url))
?>
