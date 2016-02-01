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
if(!$_GET["mysql_ip"])
{
	echo "没有输入mysql ip";
	return;
}
if(!$_GET["mysql_port"])
{
	echo "没有输入mysql端口";
	return;
}
if(!$_GET["mysql_user"])
{
	echo "没有输入mysql账号";
	return;
}

if(!$_GET["player_uin"])
{
	echo "没有输入玩家uin";
	return;
}

$redis_ip = $_GET["redis_ip"];
$redis_port = $_GET["redis_port"];
$mysql_ip = $_GET["mysql_ip"];
$mysql_port = $_GET["mysql_port"];
$mysql_user = $_GET["mysql_user"];
$mysql_password = $_GET["mysql_password"];
$player_uin = $_GET["player_uin"];

$redis = new Redis();
if($redis->connect($redis_ip, $redis_port, 10) == false)
    echo "redis 连接失败";
else
{
    $result = $redis->hGetall("uin_mapping");

    if($result)
    {
        foreach($result as $key => $val)
        {
            if($val == $player_uin)
            { 
                $mysql_con = mysql_connect($mysql_ip.":".$mysql_port, $mysql_user, $mysql_password);
                if ($mysql_con && mysql_select_db("fgame", $mysql_con))
                {
                    $redis->hDel("uin_mapping", $key);
                    $result=mysql_query("DELETE FROM uin_mapping where uin = '" . $player_uin ."'"); 
                    echo "openid : " . $key . " is deleted";
                    mysql_close($mysql_con);
                }
                else
                    echo "mysql connect faild";
                break;
            } 
        }
    }
    $redis->close();
}

?>
