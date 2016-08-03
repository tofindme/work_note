## mysql

> mysql是一个免费的开源的关系型数据库系统，为很多企业提供了更多便利，最近在了解这个问题。

mysql提供了多种引擎机制,最熟悉的也是myisam和innodb，列出以下其中的区别.


myisam不支持事务，innodb支持事务
每张MyISAM 表被存放在三个文件 。frm 文件存放表格定义。 数据文件是MYD (MYData) 。索引文件是MYI (MYIndex) 引伸。，innodb是存在一起
InnoDB支持数据行锁定，MyISAM不支持行锁定，只支持锁定整个表

mysql命令行默认是自动提交事务的，可以显示的开始事务begin&start session

事务隔离级别分为系统级和会话级别，可以用下面方式查看。
SELECT @@tx_isolation;
SELECT @@session.tx_isolation;
SELECT @@global.tx_isolation;

innodb是一个基于MVVC(多版本并发控制系统)，在MVCC并发控制中，读操作可以分成两类：快照读 (snapshot read)与当前读 (current read)。快照读，读取的是记录的可见版本 (有可能是历史版本)，不用加锁。当前读，读取的是记录的最新版本，并且，当前读返回的记录，都会加上锁，保证其他事务不会再并发修改这条记录。

在一个支持MVCC并发控制的系统中，哪些读操作是快照读？哪些操作又是当前读呢？以MySQL InnoDB为例：
 

- 快照读：简单的select操作，属于快照读，不加锁。(当然，也有例外，下面会分析)
select * from table where ?;
 

- 当前读：特殊的读操作，插入/更新/删除操作，属于当前读，需要加锁。
select * from table where ? lock in share mode;
select * from table where ? for update;
insert into table values (…);
update table set ? where ?;
delete from table where ?;
所有以上的语句，都属于当前读，读取记录的最新版本。并且，读取之后，还需要保证其他并发事务不能修改当前记录，对读取记录加锁。其中，除了第一条语句，对读取记录加S锁 (共享锁)外，其他的操作，都加的是X锁 (排它锁)。

又称读锁，若事务T对数据对象A加上S锁，则事务T可以读A但不能修改A，其他事务只能再对A加S锁，而不能加X锁，直到T释放A上的S锁。

这保证了其他事务可以读A，但在T释放A上的S锁之前不能对A做任何修改。


又称写锁。若事务T对数据对象A加上X锁，事务T可以读A也可以修改A，其他事务不能再对A加任何锁，直到T释放A上的锁。

这保证了其他事务在T释放A上的锁之前不能再读取和修改A。


### 数据库面对一些多用户对数据的操作可能产生下面几种现像

脏读，不可重复读，幻象读

脏读 （dirty read）事务T1更新了一行记录的内容，但是并没有提交所做的修改。事务T2读取更新后的行，然后T1执行回滚操作，取消了刚才所做的修改。现在T2所读取的行就无效了。

不可重复读取 （nonrepeatable read）事务T1读取一行记录，紧接着事务T2修改 了T1刚才读取的那一行记录。然后T1又再次读取这行记录，发现与刚才读取的结果不同。这就称为“不可重复”读，因为T1原来读取的那行记录已经发生了变化。

幻像读取 （phantom read）事务T1读取一条指定的WHERE子句所返回的结果集。然后事务T2新插入 一行记录，这行记录恰好可以满足T1所使用的查询条件中的WHERE 子句的条件。然后T1又使用相同的查询再次对表进行检索，但是此时却看到了事务T2刚才插入的新行。这个新行就称为“幻像”，因为对T1来说这一行就像突 然出现的一样。

2.事务的隔离级别

从级别低到高依次为：

READ UNCOMMITTED 幻像读、不可重复读和脏读都允许。

READ COMMITTED 允许幻像读、不可重复读，但不允许脏读。

REPEATABLE READ 允许幻像读，但不允许不可重复读和脏读。InnoDB默认级别

SERIALIZABLE 幻像读、不可重复读和脏读都不允许。

但是InnoDB的可重复读隔离级别和其他数据库的可重复读是有区别的，不会造成幻象读（phantom read）。

ORACLE数据库支持 READ COMMITTED 和 SERIALIZABLE ，不支持 READ UNCOMMITTED 和 REPEATABLE READ

[事务和锁](http://www.cnblogs.com/zhaoyl/p/4121010.html)
[MySQL索引背后的数据结构及算法原理](http://blog.jobbole.com/24006/)