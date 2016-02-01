
#### tcp抓包命令
`tcpdump -i eth0 host 10.10.2.39 and port 6379`

#### 根据索引结点来删除
`find ./ -inum | xargs rm -rf`

#### 设置日期
`date -s "2015-12-19 00:00:00"`

#### 查看网络相关的东西
`netstat -antpl`

#### 系统调用跟踪
- 追踪程序 `strace name -o output.txt -T`
- 追踪进程 `strace -p pid -o output.txt`

#### 时间设置
- 时区设置
	- 修改/etc/sysconfig/clock里的ZONE="Asia/Shanghai"
	- ln -sf /usr/share/zoneinfo/Asia/Shanghai /etc/localtime
- 设置硬件时间，再同步系统时间
	- hwclock --set --date="2015-12-25 18:00:00" 
	- hwclock --hctosys

#### 导出库的表结构
`mysqldump -h183.61.111.199 -P3306 -u username -ppassword -d oss_fgame2_date > /home/yibin/oss1.sql`

#### grep查找时过滤查找进程的本身
`ps -ef|grep nginx|grep -v grep`

#### nslookup 查看使用的dns命令
`nslookup www.baidu.com`

#### 查看.gz包文件列表
`gzip -dc s.tar.gz | tar tvf -`

#### 查看linux硬件信息
`dmsg`					
`cat /proc/cpuinfo`		
`cat /proc/meminfo`		
`lshw`					
`lsblk`					
`free -m`				
`free -m`				