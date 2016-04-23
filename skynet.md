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
5. sample架子理解




