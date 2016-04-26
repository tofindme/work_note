# skynet 思路整理

> skynet是一个云风大大开源的一款基于c+lua的框架，架子由c语言编写完成,逻辑上用lua来实现的一个框架

如果不关心框架的话，至少需要知道这框架里面有些什么东西，知道他的来龙去脉，由于工作原由断断续续的深入了解这个框架。由于日常开发没太在意底层实现原理，有时看下底层代码后当时知道了原理，之后再来看时基本忘记得差不多了。所以由此需要做一些笔记和深入的思考


------

#### 1. 两个服务之间通信就必须知道消息发送方和消息接收方以及消息和消息的长度，skynet里面对发送一条消息的参数注释如下:

```c++
    uint32_t address
     string address
    uint32_t type
    uint32_t session
    string message
     lightuserdata message_ptr
     uint32_t len
```

- address 指目标接收方的服务，服务用一个`uint32`的值来标识，也可以为一个服务取一个`string`类型的名称来唯一
- type    指发送的消息的类型
- session 指消息发送方对此次发送这条消息的会话标识,默认传0，传空底层会为其生成一个
- message 指消息内容,消息内容有可能也是一个`message_ptr`


```c
#define PTYPE_TAG_DONTCOPY 0x10000             //用 type | PTYPE_TAG_DONTCOPY 来指定消息是否需要copy一份 
#define PTYPE_TAG_ALLOCSESSION 0x20000         //用 type | PTYPE_TAG_ALLOCSESSION 来指定是否需要创建session 
```

<color=red>
一直在想type的值的设置和取值为什么这样设计，也查阅了一些位操作方面的知识，还是不太知道，后问了下一老司机同事，他翻开云风大大博客对这样的设计已经给出了答案，阅读和思考后最终还是清楚了。

因为type的值是一个byte，取值范围也就是0-255，一个在服务内传递的消息的在16M(24个bit)以内，所以把type的值放在sz的最高位来保存。

</color>


**具体可看`skynet\lualib-src\lua-skynet.c ——> _send(...) `函数实现**


> 服务与服务之间实际传递的消息结构体如下:

```c
struct skynet_message {
    uint32_t source; //来源哪个服务
    int session; //发送方此次会话的标识,非阻塞值为0，阻塞调用为发送方递增的值
    void * data; //发送的消息体
    size_t sz; //发送的消息大小
};

#define MESSAGE_TYPE_MASK (SIZE_MAX >> 8)
#define MESSAGE_TYPE_SHIFT ((sizeof(size_t)-1) * 8)

```

**tips：**
> 消息类型保存在 `sz |= (size_t)type << MESSAGE_TYPE_SHIFT`
> 拿回类型用 `sz >> MESSAGE_TYPE_SHIFT` 长度用`sz & MESSAGE_TYPE_MASK`

*type的值包括以下:*

```lua
local skynet = {
    -- read skynet.h
    PTYPE_TEXT = 0,
    PTYPE_RESPONSE = 1,
    PTYPE_MULTICAST = 2,
    PTYPE_CLIENT = 3,
    PTYPE_SYSTEM = 4,
    PTYPE_HARBOR = 5,
    PTYPE_SOCKET = 6,
    PTYPE_ERROR = 7,
    PTYPE_QUEUE = 8,    -- used in deprecated mqueue, use skynet.queue instead
    PTYPE_DEBUG = 9,
    PTYPE_LUA = 10,
    PTYPE_SNAX = 11,
}
```

<color=red>
一直在想type的值的设置和取值为什么这样设计，也查阅了一些位操作方面的知识，还是不太知道，后问了下一老司机同事，他翻开云风大大博客对这样的设计已经给出了答案，阅读和思考后最终还是清楚了。

因为type的值是一个byte，取值范围也就是0-255，一个在服务内传递的消息的在16M(24个bit)以内，所以把type的值放在sz的最高位来保存。所以在取type值的时候sz的二进制左移MESSAGE_TYPE_SHIFT即可，
而sz的值的话只要取除去最高位再sz的二进制右移高位即MESSAGE_TYPE_MASK。
</color>


#### 2. skynet里面是由各种lua服务来组成的，必然服务之间需要能信来往，当然也就包含阻塞发消息和非阻塞的方式


> 服务之间的消息流如下:


```seq
Service A->Service B: Hello B,我发了一条消息给你嘞!
Note right of Service B: Service B 可能会阻塞调用其它服务哦！
Service B-->Service A: Yes,我收到了,这是我回你的包！
```

> 虚线代表有些是不需要B回包的，此次调用可能是阻塞或非阻塞


- 非阻塞调用

    ```lua
function skynet.send(addr, typename, ...)
    local p = proto[typename]
    return c.send(addr, p.id, 0 , p.pack(...))
end
    ```

- 阻塞调用

    ```lua
function skynet.call(addr, typename, ...)
    local p = proto[typename]
    local session = c.send(addr, p.id , nil , p.pack(...))
    if session == nil then
        error("call to invalid address " .. skynet.address(addr))
    end
    return p.unpack(yield_call(addr, session))
end
    ```

* 阻塞与非阻塞的send函数的第三个参数分别为nil和0，nil意思就是session是由skynet分配来分配
* <font color=red>阻塞调用，如果收到阻塞调用的服务再阻塞去调其它服务,所有阻塞调用的服务的当前coroutine都会进入睡眠状态，等待回包被唤醒，进入睡眠并不影响lua_Stat进行其它的任何工作。</font>
* <font color=red>如果服务的dispatch函数没有返回值则不能用call来调用此服务，否则call的coroutine会一直阻塞中</font>

**代码实现在skynet\lualib\skynet.lua ——> raw_dispatch_message(...)函数里面,实际此函数也是每个服务收到消息后的回调函数**

**<font color=red>消息的内存管理是由发送方申请，消息接收端在回调成功后释放掉</font>**

```c
static void
dispatch_message(struct skynet_context *ctx, struct skynet_message *msg) {
    assert(ctx->init);
    CHECKCALLING_BEGIN(ctx)
    pthread_setspecific(G_NODE.handle_key, (void *)(uintptr_t)(ctx->handle));
    int type = msg->sz >> MESSAGE_TYPE_SHIFT;
    size_t sz = msg->sz & MESSAGE_TYPE_MASK;
    if (ctx->logfile) {
        skynet_log_output(ctx->logfile, msg->source, type, msg->session, msg->data, sz);
    }
    if (!ctx->cb(ctx, ctx->cb_ud, type, msg->session, msg->source, msg->data, sz)) {
        skynet_free(msg->data); //内存释放
    } 
    CHECKCALLING_END(ctx)
}
//代码文件  `skynet\skynet-src\skynet_server.c`
```

------



##  需要深入理解的点

1. 进程启动过程 
2. 回调函数调用
4. socket 
5. socket最大发送值 64M
6. sample架子理解

## 服务名
- skynet为了方便针记住某个服务，可以为每个独立的服务起一个名字，名字格式`.name`


> 一个服务的名字可以重复设置吗？
> 两个服务的名字可以相同吗？

-----

带着这些疑问就去翻阅代码去解决脑海里的疑点，首先从`skynet.name('.myservice')`的代码跟到他调用的c函数，所有处理针对服务的一些函数都在`skynet_server.c`文件里面，最后按参数找到最终处理名字的逻辑`skynet_handle.c`，大体看了下简单的实现,代码说明按自己的理解整理下。

- 命令映入结构
```c
static struct command_func cmd_funcs[] = {
    { "TIMEOUT", cmd_timeout },
    { "REG", cmd_reg },
    { "QUERY", cmd_query },
    { "NAME", cmd_name },
    { "EXIT", cmd_exit },
    { "KILL", cmd_kill },
    { "LAUNCH", cmd_launch },
    { "GETENV", cmd_getenv },
    { "SETENV", cmd_setenv },
    { "STARTTIME", cmd_starttime },
    { "ENDLESS", cmd_endless },
    { "ABORT", cmd_abort },
    { "MONITOR", cmd_monitor },
    { "MQLEN", cmd_mqlen },
    { "LOGON", cmd_logon },
    { "LOGOFF", cmd_logoff },
    { "SIGNAL", cmd_signal },
    { NULL, NULL },
};
```

- 数据结构

```c
struct handle_name {
    char * name;
    uint32_t handle;
};

struct handle_storage {
    struct rwlock lock;

    uint32_t harbor;
    uint32_t handle_index;
    int slot_size;
    struct skynet_context ** slot;
    
    int name_cap;
    int name_count;
    struct handle_name *name;
};

//handle_storage所存一个skynet进程下面所有lua服务的handle和所有为服务设置了名称的handle_name

```

## 服务名字代码说明下服务名set和get

- 服务名set

```c
static const char *
_insert_name(struct handle_storage *s, const char * name, uint32_t handle) {
    int begin = 0;
    int end = s->name_count - 1;
    while (begin<=end) {
        int mid = (begin+end)/2;
        struct handle_name *n = &s->name[mid];
        int c = strcmp(n->name, name);
        if (c==0) {
            return NULL;
        }
        if (c<0) {
            begin = mid + 1;
        } else {
            end = mid - 1;
        }
    }
    char * result = skynet_strdup(name);

    _insert_name_before(s, result, handle, begin);

    return result;
}

//用二分法遍历已经注册了名称的数组，如果找到同名的服务直接return(并不会报错，lua层只会得到nil最好用assert来处理),否则按字符串的大小插入合适的位置
```


- 服务名get

> 通过服务名来找handle

```c
uint32_t 
skynet_handle_findname(const char * name) {
    struct handle_storage *s = H;

    rwlock_rlock(&s->lock);

    uint32_t handle = 0;

    int begin = 0;
    int end = s->name_count - 1;
    while (begin<=end) {
        int mid = (begin+end)/2;
        struct handle_name *n = &s->name[mid];
        int c = strcmp(n->name, name);
        if (c==0) {
            handle = n->handle;
            break;
        }
        if (c<0) {
            begin = mid + 1;
        } else {
            end = mid - 1;
        }
    }

    rwlock_runlock(&s->lock);

    return handle;
}

//同样也是二分法来查找已知的服务名
```

**服务的handle是怎么够用的？**

```c
uint32_t
skynet_handle_register(struct skynet_context *ctx) {
    struct handle_storage *s = H;

    rwlock_wlock(&s->lock);
    
    for (;;) {
        int i;
        for (i=0;i<s->slot_size;i++) {
            uint32_t handle = (i+s->handle_index) & HANDLE_MASK;
            int hash = handle & (s->slot_size-1);
            if (s->slot[hash] == NULL) {
                s->slot[hash] = ctx;
                s->handle_index = handle + 1;

                rwlock_wunlock(&s->lock);

                handle |= s->harbor;
                return handle;
            }
        }
        assert((s->slot_size*2 - 1) <= HANDLE_MASK);
        struct skynet_context ** new_slot = skynet_malloc(s->slot_size * 2 * sizeof(struct skynet_context *));
        memset(new_slot, 0, s->slot_size * 2 * sizeof(struct skynet_context *));
        for (i=0;i<s->slot_size;i++) {
            int hash = skynet_context_handle(s->slot[i]) & (s->slot_size * 2 - 1);
            assert(new_slot[hash] == NULL);
            new_slot[hash] = s->slot[i];
        }
        skynet_free(s->slot);
        s->slot = new_slot;
        s->slot_size *= 2;
    }
}

//后续要好好分析下这段代码
```

- handle值转成`:`+八位十六进是数组成

```c
static void
id_to_hex(char * str, uint32_t id) {
    int i;
    static char hex[16] = { '0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F' };
    str[0] = ':';
    for (i=0;i<8;i++) {
        str[i+1] = hex[(id >> ((7-i) * 4))&0xf];
        //32位占四*8个bit位，每四位得到一个十六进,总共得到8个十六进制数
    }
    str[9] = '\0';
}
```