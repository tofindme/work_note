<?php
require_once(dirname(__FILE__)."/../title.php");
?>
<div align="right">
<h1 color='red'>查询玩家信息</h1>
<form name="input" action="opt/search_player_info_submit.php" method="get" target="blank" align="right">
	<div>
	redis-ip:
	<input type="text" name="redis_ip" value="10.10.1.134"/>
	</div>
	<div>
	redis-port:
	<input name="redis_port" value="6379" onkeyup="if(/\D/.test(this.value)){alert('只能输入数字');this.value='';}" type="text"/>
	</div>
	<div>
	玩家UIN:
	<input type="text" name="player_uin" onkeyup="if(/\D/.test(this.value)){alert('只能输入数字');this.value='';}"/>
	</div>
	<div>
	<input type="submit" value="查询"/>
</form>
</div>
