
# Lua

> Lua是一门脚本语言，入门非常简单，只要有任何一门语言经验就能快速用Lua来开发自己的程序，弄懂基本的数据类型以及最重要的table数据类型以及metatable之类的就是这门语言最基础的东西了，下面说下基本的数据类型以及Lua和其它语言是如何交互的

**任何语言都是easy to learn,hard to master**


#### Lua的数据类型以及区分哪些对像是要被GC的


##### 1. 基础数据类型

> Lua中所有的数据类型都包含上面9种，其中light userdata和userdata是我们不常用到的，其它是经常会用到的。因为Lua是静态类型的语言，并没有编译类型检查，所以变量是可以随意指定以上类型的，当需要GC的对象没有变量引用时就会记录等待被GC,基本的数据类型在`lua.h`里面,Lua中的number类型用64位来表示浮点型来整数的

```c
#define LUA_TNIL        0
#define LUA_TBOOLEAN        1
#define LUA_TLIGHTUSERDATA  2
#define LUA_TNUMBER     3
#define LUA_TSTRING     4
#define LUA_TTABLE      5
#define LUA_TFUNCTION       6
#define LUA_TUSERDATA       7
#define LUA_TTHREAD     8
#define LUA_NUMTAGS     9

```  

**userdata 类型允许将 C 中的数据保存在 Lua 变量中。 用户数据类型的值是一个内存块， 有两种用户数据： 完全用户数据 ，指一块由 Lua 管理的内存对应的对象； 轻量用户数据 ，则指一个简单的 C 指针。**

##### 2. 数据类型在Lua底层如何表示

> Lua中基础的数据类型都是用TValue来表示的,如下结构在`lobject.h`里面

```c
/*
** Common type for all collectable objects
*/
typedef struct GCObject GCObject;


/*
** Common Header for all collectable objects (in macro form, to be
** included in other objects)
*/
#define CommonHeader    GCObject *next; lu_byte tt; lu_byte marked


/*
** Common type has only the common header
*/
struct GCObject {
  CommonHeader;
};



/*
** Tagged Values. This is the basic representation of values in Lua,
** an actual value plus a tag with its type.
*/

/*
** Union of all Lua values
*/
typedef union Value {
  GCObject *gc;    /* collectable objects */
  void *p;         /* light userdata */
  int b;           /* booleans */
  lua_CFunction f; /* light C functions */
  lua_Integer i;   /* integer numbers */
  lua_Number n;    /* float numbers */
} Value;


#define TValuefields    Value value_; int tt_


typedef struct lua_TValue {
  TValuefields;
} TValue;


```


##### 3. 数据类型检查和可变类型

```c

/*
** Extra tags for non-values
*/
#define LUA_TPROTO  LUA_NUMTAGS     /* function prototypes */
#define LUA_TDEADKEY    (LUA_NUMTAGS+1)     /* removed keys in tables */

/*
** number of all possible tags (including LUA_TNONE but excluding DEADKEY)
*/
#define LUA_TOTALTAGS   (LUA_TPROTO + 2)


/*
** tags for Tagged Values have the following use of bits:
** bits 0-3: actual tag (a LUA_T* value)
** bits 4-5: variant bits
** bit 6: whether value is collectable
*/


/*
** LUA_TFUNCTION variants:
** 0 - Lua function
** 1 - light C function
** 2 - regular C function (closure)
*/

/* Variant tags for functions */
#define LUA_TLCL    (LUA_TFUNCTION | (0 << 4))  /* Lua closure */
#define LUA_TLCF    (LUA_TFUNCTION | (1 << 4))  /* light C function */
#define LUA_TCCL    (LUA_TFUNCTION | (2 << 4))  /* C closure */


/* Variant tags for strings */
#define LUA_TSHRSTR (LUA_TSTRING | (0 << 4))  /* short strings */
#define LUA_TLNGSTR (LUA_TSTRING | (1 << 4))  /* long strings */


/* Variant tags for numbers */
#define LUA_TNUMFLT (LUA_TNUMBER | (0 << 4))  /* float numbers */
#define LUA_TNUMINT (LUA_TNUMBER | (1 << 4))  /* integer numbers */


/* Bit mark for collectable types */
#define BIT_ISCOLLECTABLE   (1 << 6)

/* mark a tag as collectable */
#define ctb(t)          ((t) | BIT_ISCOLLECTABLE)


#define val_(o)     ((o)->value_)


/* raw type tag of a TValue */
#define rttype(o)   ((o)->tt_)

/* tag with no variants (bits 0-3) */
#define novariant(x)    ((x) & 0x0F)

/* type tag of a TValue (bits 0-3 for tags + variant bits 4-5) */
#define ttype(o)    (rttype(o) & 0x3F)

/* type tag of a TValue with no variants (bits 0-3) */
#define ttnov(o)    (novariant(rttype(o)))


/* Macros to test type */
#define checktag(o,t)       (rttype(o) == (t))
#define checktype(o,t)      (ttnov(o) == (t))
#define ttisnumber(o)       checktype((o), LUA_TNUMBER)
#define ttisfloat(o)        checktag((o), LUA_TNUMFLT)


```

**为什么是`n<<4`位？基本类型最大的是`LUA_TOTALTAGS`，完全可以用四位二进制来表示，所以左移4位，1左移后代表第5位，2左移后代表第5位，当然也可以用1左移5位来代替，因为0-3位代表LUA_T*类型，4-5位代表variant的类型，6位可回收的类型，所以用两个函数(`checktag checktype`)来做类型检查**

##### 4. 哪些类型需要被GC

> 下面列出了哪些对象需要被GC掉

```c
/*
** Union of all collectable objects (only for conversions)
*/
union GCUnion {
  GCObject gc;  /* common header */
  struct TString ts;
  struct Udata u;
  union Closure cl;
  struct Table h;
  struct Proto p;
  struct lua_State th;  /* thread */
};

```



#### Lua和其它语言怎么交互的

[参考](http://www.cnblogs.com/sevenyuan/p/4511808.html)

> 以下说明都是lua5.3里面lua和c相互调用的说明

交互参数是用栈来实现的，最初大小为40，有5个预留用作他用,栈里存的值为TValue

```c

/* minimum Lua stack available to a C function */
#define LUA_MINSTACK  20

/* extra stack space to handle TM calls and some other extras */
#define EXTRA_STACK   5


#define BASIC_STACK_SIZE        (2*LUA_MINSTACK)


```

**实现是用malloc开辟了一块BASIC_STACK_SIZE个TValue大小的内存**

有了这个栈，与任何语言只要从这个栈里面存取参数就可以相互调用了。


#### 1. c与stack的交互

```c
/*
** access functions (stack -> C)
*/

LUA_API int             (lua_isnumber) (lua_State *L, int idx);
LUA_API int             (lua_isstring) (lua_State *L, int idx);
LUA_API int             (lua_iscfunction) (lua_State *L, int idx);
LUA_API int             (lua_isinteger) (lua_State *L, int idx);
LUA_API int             (lua_isuserdata) (lua_State *L, int idx);
LUA_API int             (lua_type) (lua_State *L, int idx);
LUA_API const char     *(lua_typename) (lua_State *L, int tp);

LUA_API lua_Number      (lua_tonumberx) (lua_State *L, int idx, int *isnum);
LUA_API lua_Integer     (lua_tointegerx) (lua_State *L, int idx, int *isnum);
LUA_API int             (lua_toboolean) (lua_State *L, int idx);
LUA_API const char     *(lua_tolstring) (lua_State *L, int idx, size_t *len);
LUA_API size_t          (lua_rawlen) (lua_State *L, int idx);
LUA_API lua_CFunction   (lua_tocfunction) (lua_State *L, int idx);
LUA_API void         *(lua_touserdata) (lua_State *L, int idx);
LUA_API lua_State      *(lua_tothread) (lua_State *L, int idx);
LUA_API const void     *(lua_topointer) (lua_State *L, int idx);

//在C里面从lua栈里面读取值


/*
** push functions (C -> stack)
*/
LUA_API void        (lua_pushnil) (lua_State *L);
LUA_API void        (lua_pushnumber) (lua_State *L, lua_Number n);
LUA_API void        (lua_pushinteger) (lua_State *L, lua_Integer n);
LUA_API const char *(lua_pushlstring) (lua_State *L, const char *s, size_t len);
LUA_API const char *(lua_pushstring) (lua_State *L, const char *s);
LUA_API const char *(lua_pushvfstring) (lua_State *L, const char *fmt,
                                                      va_list argp);
LUA_API const char *(lua_pushfstring) (lua_State *L, const char *fmt, ...);
LUA_API void  (lua_pushcclosure) (lua_State *L, lua_CFunction fn, int n);
LUA_API void  (lua_pushboolean) (lua_State *L, int b);
LUA_API void  (lua_pushlightuserdata) (lua_State *L, void *p);
LUA_API int   (lua_pushthread) (lua_State *L);

//在C里面往lua栈里面写入值

```



#### 2. lua与stack的交互


```c

/*
** get functions (Lua -> stack)
*/
LUA_API int (lua_getglobal) (lua_State *L, const char *name);
LUA_API int (lua_gettable) (lua_State *L, int idx);
LUA_API int (lua_getfield) (lua_State *L, int idx, const char *k);
LUA_API int (lua_geti) (lua_State *L, int idx, lua_Integer n);
LUA_API int (lua_rawget) (lua_State *L, int idx);
LUA_API int (lua_rawgeti) (lua_State *L, int idx, lua_Integer n);
LUA_API int (lua_rawgetp) (lua_State *L, int idx, const void *p);

LUA_API void  (lua_createtable) (lua_State *L, int narr, int nrec);
LUA_API void *(lua_newuserdata) (lua_State *L, size_t sz);
LUA_API int   (lua_getmetatable) (lua_State *L, int objindex);
LUA_API int  (lua_getuservalue) (lua_State *L, int idx);

//lua从栈里面读取值

/*
** set functions (stack -> Lua)
*/
LUA_API void  (lua_setglobal) (lua_State *L, const char *name);
LUA_API void  (lua_settable) (lua_State *L, int idx);
LUA_API void  (lua_setfield) (lua_State *L, int idx, const char *k);
LUA_API void  (lua_seti) (lua_State *L, int idx, lua_Integer n);
LUA_API void  (lua_rawset) (lua_State *L, int idx);
LUA_API void  (lua_rawseti) (lua_State *L, int idx, lua_Integer n);
LUA_API void  (lua_rawsetp) (lua_State *L, int idx, const void *p);
LUA_API int   (lua_setmetatable) (lua_State *L, int objindex);
LUA_API void  (lua_setuservalue) (lua_State *L, int idx);

//lua往栈里面写入值

```
