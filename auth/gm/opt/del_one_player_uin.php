<?php
require_once(dirname(__FILE__)."/../title.php");
?>
<div align="right">
<h1 color='red'>删除一个玩家数据</h1>
<form name="input" action="opt/del_one_player_uin_submit.php" method="get" target="blank" align="right">
	<div>redis-ip:
	<input type="text" name="redis_ip" value="10.10.1.134"/>
	</div>
	<div>redis-port:
	<input name="redis_port" value="6379" onkeyup="if(/\D/.test(this.value)){alert('只能输入数字');this.value='';}" type="text"/>
	</div>
	<div>mysql-ip:
	<input name="mysql_ip" value="10.10.1.134" type="text"/>
	</div>
	<div>mysql-port:
	<input name="mysql_port" value="3306" onkeyup="if(/\D/.test(this.value)){alert('只能输入数字');this.value='';}" type="text"/>
	</div>
	<div>mysql-user:
	<input name="mysql_user" value="root" type="text"/>
	</div>
	<div>mysql-password:
	<input name="mysql_password" value="root" type="password"/>
	</div>
	<div>玩家UIN:
	<input type="text" name="player_uin" onkeyup="if(/\D/.test(this.value)){alert('只能输入数字');this.value='';}"/>
	</div>
	<input type="submit" value="删除"/>
</form>
</div>
