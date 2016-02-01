<?php

require_once(dirname(__FILE__)."/../title.php");
require_once(dirname(__FILE__)."/../util.php");

if(!$_GET["redis_ip"])
{
	echo "没有输入redis ip";
	return;
}
if(!$_GET["redis_port"])
{
	echo "没有输入redis端口";
	return;
}

if(!$_GET["player_uin"])
{
	echo "没有输入玩家uin";
	return;
}

$redis_ip = $_GET["redis_ip"];
$redis_port = $_GET["redis_port"];
$player_uin = $_GET["player_uin"];

$redis = new Redis();
if($redis->connect($redis_ip, $redis_port, 10) == false)
    echo "redis 连接失败";
else
{
    $result = $redis->hGet("account", $player_uin);
    if($result)
        echo $result;
    else
        echo "玩家不存";
}

?>
