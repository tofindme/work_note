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

if(!$_GET["start_time"])
{
    echo "没有输入开始时间";
    return;
}
if(!$_GET["end_time"])
{
    echo "没有输入结束时间";
    return;
}

$server_ip = $_GET["server_ip"];
$server_port = $_GET["server_port"];
$id = $_GET["id"];
$start_time = strtotime($_GET["start_time"]);
$end_time = strtotime($_GET["end_time"]);

$url = "http://".$server_ip.":".$server_port."/gm?";
$url = $url."gmtype=activity&id=".$id."&opt=add"."&start_time=".$start_time."&end_time=".$end_time."&extra=";

echo (https_get($url))
?>
