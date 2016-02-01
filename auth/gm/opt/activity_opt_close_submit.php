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
if(!$_GET["id"])
{
    echo "没有输入活动id";
    return;
}

$server_ip = $_GET["server_ip"];
$server_port = $_GET["server_port"];
$id = $_GET["id"];

$url = "http://".$server_ip.":".$server_port."/gm?";
$url = $url."gmtype=activity&id=".$id."&opt=close&start_time=1&end_time=1&extra=";

echo (https_get($url))
?>
