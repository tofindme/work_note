# mongodb安装

> 因mongodb是基于document的存储类型，无固定schema的一个类似mysql的数据库，所以用来存储和分析oss日志。

本次在Red Hat的系统上安装的是mongodb 3.2的版本，不用默认的源安装，我们添加一个centos的yum源


- 创建`/etc/yum.repos.d/mongodb-org-2.6.repo`文件,文件内容如下:

```toml
[mongodb-org-3.2]
name=MongoDB Repository
baseurl=https://repo.mongodb.org/yum/redhat/$releasever/mongodb-org/3.2/x86_64/
gpgcheck=1
enabled=1
gpgkey=https://www.mongodb.org/static/pgp/server-3.2.asc
```

- 运行`sudo yum install -y mongodb-org`

- 启动mongod`sudo service mongod start`像mysqld启动服务

- 连接`mongo`像mysql命令一样可以连接数据库


## 添加用户

http://blog.csdn.net/chen88358323/article/details/50206651

> mongodb是用

use admin
db.createUser({user:"yibin", pwd:"yibin", roles:[{role:"readWrite" db:"test"}]})


CRUD

- C(Insert)
db.test.insert({name:"yibin",age:"20"})

- R(Read)
db.test.find({})
db.test.find({name:"yibin"})

- U(Update)
db.test.update({condition}, {$set:{}})

- D(Delete)
db.test.remove({_id:""})
db.test.remove({name:"yibin"})


AGGREGATE

[refer to](https://docs.mongodb.com/manual/reference/sql-aggregation-comparison/)

- 按名字分组

db.test.aggregate([
{$math:{name:"yibin"}},
{$group:{_id:"name"}},
])

- 按account_id分组并拿出最小的日期

db.login2.aggregate([{$group:{_id:"$account_id", date:{$min:"$date"}}}])


db.login2.aggregate([{$match:
                        {
                            account_id:{$in:["test1","test2"]}
                        }
                    },
                    {$group:
                        {
                            _id:{account_id:"$account_id",register_date:"$register_date",date:{$substr:["$date",0,10]}},
                            date:{$min}
                        }
                    }
                ])


#### 留存实践

> 利用mongodb的map reduce功能来实现新用户留存功能

也样也可用来实现活跃用户统计(指从注册到某自然日活跃的用户数)

```javascripte

//[参考](http://blog.csdn.net/jq0123/article/details/49762355）

//留存的定义采用的是
//新增账号第X日：某日新增的账号中，在新增日后第X日有登录行为记为留存

// 留存率统计脚本
// 参考文档：留存率统计.txt
// Usage:
// mongo my.mongo.host retention.js

print("计算留存开始于: " + Date());
db = db.getSiblingDB("oss");  // use mydb

var begin = "2015-02-01";//getStartDate();
var end = "2015-02-04";// formatDate(new Date());
print("准备计算 " + begin + " 到 " + end + "的留存!");

if (begin < end) {
    insertDefaultResult(begin, end);
    calcRegisterCount(begin, end);
    calcRetention(begin, end);
    print("计算留存结束于: " + Date());
    print("计算结束.");
} else {
    print("Do nothing.");
}

// Internal functions.

// 获取统计开始日期，之前的已经统计完成，无需重做。
// 返回字符串，格式："2015-01-01"
// 获取 retention.result 的最大 date + 1天, 仅须处理该天及以后的数据。
// 如果是初次运行，retention.result 为空，须读取 retention.register 的最早日期作为开始。
function getStartDate() {
    var lastResultDate = getLastResultDate();
    if (null == lastResultDate) {
        return getFirstRegisterDate();
    }
    // 加一天
    return getNextDate(lastResultDate);
}

// 获取最早的 retention.register 日期。
function getFirstRegisterDate() {
    var cursor = db.register.find(
        {date : {$gt : "2015-01-01"}},  // 除去 null
        {_id : 0, date : 1}
    ).sort({date : 1}).limit(1);
    if (cursor.hasNext()) {
        return cursor.next().date;
    }
    return formatDate(new Date());
}

// 获取 retention.result 中最后的 date 字段。
// 无date字段则返回null。
// 正常返回如："2015-01-01"
function getLastResultDate() {
    // _id 为日期串
    var cursor = db.result.find(
        {}, {_id : 1}).sort({_id : -1}).limit(1);
    if (cursor.hasNext()) {
        return cursor.next()._id;
    }
    return null;
}

function add0(m) {
    return m < 10 ? '0' + m : m;
}

// Return likes: "2015-01-02"
function formatDate(str) {
    var date = new Date(str);
    var y = date.getFullYear();
    var m = date.getMonth() + 1;  // 1..12
    var d = date.getDate();
    return  y + '-' + add0(m) + '-' + add0(d);
}

// "2015-12-31" -> "2016-01-01"
function getNextDate(dateStr) {
    //时间需要+8小时
    dateStr = dateStr.replace(/-/g,"/");
    var dateObj = new Date(dateStr + " 08:00:00");
    var nextDayTime = dateObj.getTime() + 24 * 3600 * 1000;
    var nextDate = new Date(nextDayTime);
    return formatDate(nextDate);
}

assert(getNextDate("2015-12-31") == "2016-01-01");
assert(getNextDate("2015-01-01") == "2015-01-02");
assert(getNextDate("2015-01-31") == "2015-02-01");

// 插入缺省结果。
// 某些天无新注册，mapreduce就不会生成该条结果，须强制插入。
function insertDefaultResult(startDate, endDate) {
    var docs = new Array();
    if (undefined == endDate){
        endDate = formatDate(new Date());
    }
    for (var dateStr = startDate;
        dateStr < endDate;
        dateStr = getNextDate(dateStr)) {
        docs.push({_id : dateStr, value : {date : dateStr, register : 0}});
    }  // for
    db.result.insert(docs);
}

// 读取 retention.register 集合，
// 计算每日新注册量, 记录于 retention.result.value.register 字段
// startDate is like: "2015-01-01"
function calcRegisterCount(startDate, endDate) {

    if (undefined == endDate){
        endDate = formatDate(new Date());
    }
    var mapFunction = function() {
        var key = this.date;
        var value = {date : key, register : 1};
        emit(key, value);
    };  // mapFunction

    var reduceFunction = function(key, values) {
        var reducedObject = {date : key, register : 0};
        values.forEach(
            function(value) {
                reducedObject.register += value.register;
            }
        )
        return reducedObject;
    };  // reduceFunction

    db.register.mapReduce(mapFunction, reduceFunction,
        {
            query: {date: {$gte: startDate, $lt: endDate}},
            out: {merge: "result"}
        }
    );  // mapReduce()
}  // function calcRegisterCount()

// 读取 retention.login 集合，
// 计算留存率，保存于 retention.result 集合。
// startDate is like: "2015-01-01"
function calcRetention(startDate, endDate) {

    if (undefined == endDate){
        endDate = formatDate(new Date());
    }
    var name = "yibin";

    var mapFunction = function() {
        var key = this.register_date;
        var rg = this.register_date.replace(/-/g,"/");
        var lg = this.date.replace(/-/g,"/");
        var registerDateObj = new Date(rg + " 08:00:00");
        var loginDateObj = new Date(lg + " 08:00:00");
        var days = (loginDateObj - registerDateObj) / (24 * 3600 * 1000);
        var value = {date : key, register : 0};
        var field = "day" + days;  // like: day1
        value[field] = 1;
        emit(key, value);
    };  // mapFunction

    //如果mapReduce的out用的reduce那么有相同key的document再会调用一次reduce和finalize函数,这样一来之前的数据就不会被覆盖
    var reduceFunction = function(key, values) {
        var reducedObject = {date : key, register : 0};
        for (var i = 1; i <= 60; i++) {
            var field = "day" + i;
            reducedObject[field] = 0;
        }

        values.forEach(
            function(value) {
                reducedObject.register += value.register;
                for (var i = 1; i <= 60; i++) {
                    var field = "day" + i;  // like: day1
                    var count = value[field];
                    if (null != count) {
                        reducedObject[field] += count;
                    }  // if
                }  // for
            }  // function
        )  // values.forEach()
        return reducedObject;
    };  // reduceFunction()

    // 这里注意==0时需要返回，这个是算出当次的mapReduce当out是reduce的时候相同key的会执行reduce和finalize函数
    var finalizeFunction = function(key, reducedVal) {
        if (0 == reducedVal.register)
            return reducedVal;
        for (var i = 1; i <= 60; i++) {
            var field = "day" + i;  // day1
            var count = reducedVal[field];
            reducedVal[field] = count * 100 / reducedVal.register;
        }
        return reducedVal;
    };  // finalizeFunction

    db.login.mapReduce(mapFunction, reduceFunction,
        {
            query: {date: {$gte: startDate, $lt: endDate}},
            out: {reduce: "result"},
            finalize: finalizeFunction,
        }
    );  // mapReduce()
}  // function calcRetention()



```





