# redis

> redis是一个非常优秀的开源项目，可以说是一本数据结构相关的活教程。它是一个k-v的基于内存的数据库，所以一般用做简单的数据库或是缓存系统，他提供了各种数据的存储string,set,hash,list等常用的数据结构都可以存储

### 阅读参考

以下是别人翻译的文章，讲的非常详细

http://origin.redisbook.com/

### redis协议

http://doc.redisfans.com/topic/protocol.html

redis协议是crlf格式的字符串消息流，能过crlf来解析请求参数以及服务器回包，具体包含有以下几类:

- `+OK\r\n`  代表服务器正确执行处理
- `-ERR\r\n` 代表服务器执行错误
- `:1\r\n` 表示返回一个数值，：后面是相应的数字节符。
- `*4\n\n` 表示消息体总共有多少行，不包括当前行,*后面是具体的行数。
- `$3\r\n` 表示下一行数据长度，不包括换行符长度\r\n,$后面则是对应的长度的数据。



### 访问频率限制

为了防止在一定时间内一个ip地址访问频率限制，用Redis的list来实现比较方便，用ip地址来用key，把最后访问时间放到链表里去。


——————————————————————————————————————————————————————————————————————————————
a13    a12    a11    a10    a9    a8    a7    a6    a5    a4    a3    a2    a1
——————————————————————————————————————————————————————————————————————————————


如图，假设上面是一个ip地址已经访问的次数，a*代表此ip访问时的时间戳

```clang
LPUSH IP a1
LPUSH IP a2
LPUSH IP a3

if (LLEN(IP) < LIMIT) {
    LPUSH IP a4
}else{
    last = LINDEX IP -1
    if ((now - last) > secs){
        //错误!在规定时间内访问过于频繁
    }else{
        LTRIM 0 9 //保留最近十次
    }
}
```

LPUSH & RPUSH

LPUSH往链表左端追加元素
RPUSH往链表右端追加无素

                                ———————————————————————————————————
LPUSH---->                                                                              <---- RPUSH
                                ———————————————————————————————————



