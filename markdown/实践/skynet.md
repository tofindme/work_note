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

<font color=red>
一直在想type的值的设置和取值为什么这样设计，也查阅了一些位操作方面的知识，还是不太知道，后问了下一老司机同事，他翻开云风大大博客对这样的设计已经给出了答案，阅读和思考后最终还是清楚了。

因为type的值是一个byte，取值范围也就是0-255，一个在服务内传递的消息的在16M(24个bit  2<sup>14</sup>*2<sup>10</sup>)以内，所以把type的值放在sz的最高位来保存。所以在取type值的时候sz的二进制左移MESSAGE_TYPE_SHIFT即可，
而sz的值的话只要取除去最高位再sz的二进制右移高位即MESSAGE_TYPE_MASK。
</font>


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



#### 3. 进程启动过程

```c
void 
skynet_start(struct skynet_config * config) {
    if (config->daemon) {
        if (daemon_init(config->daemon)) {
            exit(1);
        }
    }
    skynet_harbor_init(config->harbor); //用于集群id初始化
    skynet_handle_init(config->harbor); //上面所说的进程内所有service的handle及保存service的名称
    skynet_mq_init(); //全局队列初始化
    skynet_module_init(config->module_path); //加载service-src下面的模块 logger harbor snlua
    skynet_timer_init(); //定时器初始化
    skynet_socket_init(); //套接字初始化

    struct skynet_context *ctx = skynet_context_new(config->logservice, config->logger);
    if (ctx == NULL) {
        fprintf(stderr, "Can't launch %s service\n", config->logservice);
        exit(1);
    }

    //lua脚本加载
    bootstrap(ctx, config->bootstrap);

    //启动线程及监听相关
    start(config->thread);

    // harbor_exit may call socket send, so it should exit before socket_free
    skynet_harbor_exit();
    skynet_socket_free();
    if (config->daemon) {
        daemon_exit(config->daemon);
    }
}

```
skynet启动进程主要与服务相关代码如上面

每个lua服务都有自己的一个队列，并且创建后就会放到全局队列里面，有工作线程会去从全局队列里面去拿服务队列并处理完一条消息后若全局队列不为空再放到全局队列里去返回下个需要处理的部队。为空则返回自己服务的队列



2. 回调函数调用
在加载lua代码的时候`skynet.start(...)`函数里面注册了回调函数

```lua
function skynet.start(start_func)
    c.callback(skynet.dispatch_message)
    skynet.timeout(0, function()
        skynet.init_service(start_func)
    end)
end
```



#### 4. skynet服务


##### 4.1 服务标识

- skynet为了方便针记住某个服务，可以为每个独立的服务起一个名字，名字格式`.name`


> 一个服务的名字可以重复设置吗？(可以)
> 两个服务的名字可以相同吗？(不可以，已经有了这样一个名字直接返回空)


-----


##### 4.1 服务名字存储及handle的存储结构和服务相关的命令映射


带着这些疑问就去翻阅代码去解决脑海里的疑点，首先从`skynet.name('.myservice')`的代码跟到他调用的c函数，所有处理针对服务的一些函数都在`skynet_server.c`文件里面，最后按参数找到最终处理名字的逻辑`skynet_handle.c`，大体看了下简单的实现,代码说明按自己的理解整理下。

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


##### 4.2 服务名字代码说明下服务名set和get

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

- handle值转换`:`+八位十六进是数组成

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

#### 5. 模块与服务之间的关系

> 工作中，在看同事写的日志模块的实现，在看代码的时候出现了一些疑问点。游戏里面的货币、战斗、登录日志、充值等一些日志的时候。由于写了一个自己的日志模块代码，并且启了各种日志分类的skynet服务。并且在目录下面创建了不同的文件，所以仔细跟踪了一下这个代码实现


之前在说进程启动过程时候有说过会初始化一些模块，这些模块包含snlua、自带的logger、以及用于集群实现的harbor模块和网关的一个模块,这次就仔细阅读了snlua和自带的logger各自模块的服务初始化过程。


```c
struct skynet_context * 
skynet_context_new(const char * name, const char *param) {
    struct skynet_module * mod = skynet_module_query(name); //查找此服务对应的模块名称

    if (mod == NULL)
        return NULL;

    void *inst = skynet_module_instance_create(mod); //创建对应的模块
    if (inst == NULL)
        return NULL;
    struct skynet_context * ctx = skynet_malloc(sizeof(*ctx));
    CHECKCALLING_INIT(ctx)

    ctx->mod = mod;
    ctx->instance = inst;
    ctx->ref = 2;
    ctx->cb = NULL;
    ctx->cb_ud = NULL;
    ctx->session_id = 0;
    ctx->logfile = NULL;

    ctx->init = false;
    ctx->endless = false;
    // Should set to 0 first to avoid skynet_handle_retireall get an uninitialized handle
    ctx->handle = 0;    
    ctx->handle = skynet_handle_register(ctx);
    struct message_queue * queue = ctx->queue = skynet_mq_create(ctx->handle);
    // init function maybe use ctx->handle, so it must init at last
    context_inc();

    CHECKCALLING_BEGIN(ctx)
    int r = skynet_module_instance_init(mod, inst, ctx, param); //初始化一个模块下面的服务 param是服务初始化的参数
    CHECKCALLING_END(ctx)
    if (r == 0) {
        struct skynet_context * ret = skynet_context_release(ctx);
        if (ret) {
            ctx->init = true;
        }
        skynet_globalmq_push(queue);
        if (ret) {
            skynet_error(ret, "LAUNCH %s %s", name, param ? param : "");
        }
        return ret;
    } else {
        skynet_error(ctx, "FAILED launch %s", name);
        uint32_t handle = ctx->handle;
        skynet_context_release(ctx);
        skynet_handle_retire(handle);
        struct drop_t d = { handle };
        skynet_mq_release(queue, drop_message, &d);
        return NULL;
    }
}

**示例**
    skynet_context_new("snlua", "bootstrap")

```

所以模块和服务是一对一对的关系，后来又看了下`skynet.newservice(...)`和`skynet.launch(...)`函数的实现，newservice是能过一个叫`.launch`的服务(此服务是在`bootstrap`服务启动的时候创建的)包装launch函数来实现的。


```lua
function skynet.newservice(name, ...)
    return skynet.call(".launcher", "lua" , "LAUNCH", "snlua", name, ...)
end

function skynet.launch(...)
    local addr = c.command("LAUNCH", table.concat({...}," "))
    if addr then
        return tonumber("0x" .. string.sub(addr , 2))
    end
end
```

下面说下模块的加载过程，模块实际就是一些动态链接库(.so)，然后实现一些共用的函数接口，模块定义的结构体如下:

```c

//存储所有模块结构体

#define MAX_MODULE_TYPE 32
struct modules {
    int count;
    struct spinlock lock;
    const char * path;
    struct skynet_module m[MAX_MODULE_TYPE];
};

struct skynet_module {
    const char * name;
    void * module;
    skynet_dl_create create;        //模块so的函数入口
    skynet_dl_init init;            //同上
    skynet_dl_release release;      //同上
    skynet_dl_signal signal;        //同上
};



```


#### 6. socket 

socket是lua层来创建的，所以他需要经过lua->c层代码，文件包含lua脚本——》lua-socket.c——》skynet_socket.c——》socket_server.c
我们来跟踪一下启动一个listen的fd.
首先是在gateserver.lua里面调用了

```lua
        socket = socketdriver.listen(address, port)
        socketdriver.start(socket)
```

- listen的过程,lua-socket.c里面调用llisten()，然后继续下面的流程

这里通过pipe来通信，通信的内容如下面的结构体说明。

llisten()
    skynet_socket_listen()
        socket_server_listen()         创建fd并绑定ip port      
            skynet_socket_poll()
                socket_server_poll()   这里循环pipe的消息处理以及event poll处理

通过forward_message返回一个PTYPE_SOCKET类型的的消息，然后再通过下面的unpack函数解压并把消息派发出去

```lua
    skynet.register_protocol {
        name = "socket",
        id = skynet.PTYPE_SOCKET,   -- PTYPE_SOCKET = 6
        unpack = function ( msg, sz )
            return netpack.filter( queue, msg, sz)
        end,
        dispatch = function (_, _, q, type, ...)
            queue = q
            if type then
                MSG[type](...)
            end
        end
    }
```



    poll

        pipe命令读取
                    _________
                    |   L      Listen继续处理poll事件
        1           |   S      退出poll事件，返回SOCKET_OPEN
                    |   B      
                    |   O

        socket事件处理

        2           events
                        accepte
                        connect
                        data

    poll后再返回给指定的service去处理消息



- 接着是start这个fd,实际是记录到socket_server这个里面去

这两步就是创建一个listen的fd了，接下来只需要accepte客户端连接了



```clang

/*
    The first byte is TYPE

    S Start socket
    B Bind socket
    L Listen socket
    K Close socket
    O Connect to (Open)
    X Exit
    D Send package (high)
    P Send package (low)
    A Send UDP package
    T Set opt
    U Create UDP socket
    C set udp address
 */
struct request_package {
    uint8_t header[8];  // 6 bytes dummy
    union {
        char buffer[256];
        struct request_open open;
        struct request_send send;
        struct request_send_udp send_udp;
        struct request_close close;
        struct request_listen listen;
        struct request_bind bind;
        struct request_start start;
        struct request_setopt setopt;
        struct request_udp udp;
        struct request_setudp set_udp;
    } u;
    uint8_t dummy[256];
};

/*
header[6] header[7] 两个字节+len(union)结构体
write(fd, &header[6], len+2)
*/


struct socket_server {
    int recvctrl_fd;
    int sendctrl_fd;
    int checkctrl;
    poll_fd event_fd;
    int alloc_id;
    int event_n;
    int event_index;
    struct socket_object_interface soi;
    struct event ev[MAX_EVENT];
    struct socket slot[MAX_SOCKET];
    char buffer[MAX_INFO];
    uint8_t udpbuffer[MAX_UDP_PACKAGE];
    fd_set rfds;
};

```




收到包如何通知gateserver?
包大小？一个没没读玩又来了另一个包怎么处理？



#### 7. sample架子理解

把下面的关系弄清楚

client

login

gateserver

agent

client  是客户端发起登录的对象
login   是处理client登录请求，成功后告知gateserver
gateserver  类似agent的一房门。主要负责管理网络这块的事件传递到agent
agent   是消息实际处理者，也就是操作fd连接的