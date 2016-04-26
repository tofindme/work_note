
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

#### linux 发送和接收windows文件命令
rz sz
    rz(recieve Zmonde)以zmonde协议来传送文件

#### 查看默认shell方式
- 查看使用的shell
    `echo $0`
    `echo $SHELL`
- 改变默认的shell
    `chsh -s /bin/zsh`

#### vi

- 单词选中 **向后vb**  **向前vw**
- w 单词前进 b 单词后退 
- windows 粘贴到linux :set paste 后格式不会再乱


### 删除很特殊的文件
- `find . -inum 441511 -delete`
> 如果文件命名很怪可以用此命令来删除

-------

# 数据库命令

#### 连接数据库
`mysql -h127.0.0.1 -uuser -P3306 -p`

#### 查看字符编码
`show variables like '%char%';`

#### 数据表中字符集设置
`show full columns from tablename;`



----------
#### golang1.6编译
http://studygolang.com/articles/3188


--------


> git 相关命令

#### git no branch解决方法
每次在master上面更新有冲突后会生成一个no branch的分支，这种情况需要在no branch下面解决冲突后再更新再提交，然后再更新用git log查看最新的commit id然后切回master分支，把头指针设置成最新的commit id这样就解决了回master分支再更新的问题

#### git branch操作
- 新建分支 `git branch name`
- 提交分支 `git branch push origin name`
- 删除分支 `git branch -d name`
- 删除远程分支 `git push origin :name`


#### git reset

在操作git时，如果某次提交有误，需要回滚到有误之前的一个commit时，可以更新代码然后创建一个新的分支，然后再到新的分支上面git reset commitid。然后再回到master分支再合并

----

- git

```sh
git branch dev
git commit -m //提交到head状态
git checkout master
git merge dev
```



直接改head头指针时，先从改到旧的地址，然后又换新的地址