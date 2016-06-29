# nginx

> nginx是一个非常流行的一个web服务器，以高并发和性能优势被人所知。设计非常好。


#### nginx框架图

<img src="./nginx_flow.png"></img>

![img](./nginx_flow.png)


#### 线程安全，可重入，异步信号安全函数

在读到nginx入口函数时有一段代码把errno的值加载到了内存里了，所以这里就讨论了标题的内容。


```c
/*
 * The strerror() messages are copied because:
 *
 * 1) strerror() and strerror_r() functions are not Async-Signal-Safe,
 *    therefore, they cannot be used in signal handlers;
 *
 * 2) a direct sys_errlist[] array may be used instead of these functions,
 *    but Linux linker warns about its usage:
 *
 * warning: `sys_errlist' is deprecated; use `strerror' or `strerror_r' instead
 * warning: `sys_nerr' is deprecated; use `strerror' or `strerror_r' instead
 *
 *    causing false bug reports.
 */


 Reentrant Function

A function whose effect, when called by two or more threads, is guaranteed to be as if the threads each executed the function one after another in an undefined order, even if the actual execution is interleaved.

Thread-Safe

A function that may be safely invoked concurrently by multiple threads. Each function defined in the System Interfaces volume of IEEE Std 1003.1-2001 is thread-safe unless explicitly stated otherwise.

Async-Signal-Safe Function

A function that may be invoked, without restriction, from signal-catching functions. No function is async-signal-safe unless explicitly described as such.



```

 上在是nginx代码里面的注释，说什么要要把错误号先读出来。读到这里遇到几个之前不熟悉的概念，所以查阅了大部分资料。


 **线程安全函数**

多线程程序一般不可避免对共享资源的竞争，一个对全局变量或资源进行读写的时候在多线程里面可能造成不可想像的后果，所以通常在函数里面对一些线程之间共享的资源进行加锁进行线程之间的同步，所以函数就是线程安全的了。

线程安全函数是指多个线程同时调用此函数，能保证安全执行，显然可重入函数只是线程安全函数的一个子集。

 **可重入函数**

可重入函数是指在任何时候任何地方调用都能保证安全的函数，无论是线程还是信号处理函数，函数一般没有共享变量或者锁之类的东西，linux下系统函数只有80多个可重入函数


 **异步信号安全函数**

信号安全函数是在信号处理函数中可以安全调用的函数。


由于linux会产生异步的信号，中断程序的执行，如果对中断信号做了处理，则会执行中我们的中断函数，执行完后，再执行我们的程序。这样一来中断处理函数必须保证是安全安全，也就是说没有对全局变量之类的东西做修改。




