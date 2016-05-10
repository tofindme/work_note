## 1. golang

> Go是Google开发的一种编译型，可平行化，并具有垃圾回收功能的编程语言。

### 2. 特点

> 玩过go的人是知道他的设计思想的，尽可能编写少量的代码来完成多的任务。也就是官网宣传的"Less is more"。然而他的确也做到了这点，代码简洁而优雅。

1. 语法简单，写过c的很快就会写go。

2. 自家的任务调度机制，尽可能的利用cpu的多核实现高并发，以及并行的垃圾回收机制

3. 完善的标准库、优秀的开源项目以及热爱go的社区。

4. 代码统一风格(gofmt)，很好的测试框架(go test)以及工具链(go tool)

5. 部署方便，编译后不依赖任何so可随处发布(交叉编译)。

6. 内存分析工具pprof用于分析线上的问题

7. go和c一样，代码里表达最多的就是结构体，不同的是go里面的结构体的成员是有访问权限的(大小写)，并且还可以有方法来表达行为

8. go还有一个重要的思想就是组合，一个结构体里面引用另外一个结构体就拥有它的行为

9. go也有面象对像的思维，就是go里面的interface，这可以代表go里面的任何类型

10. go的通道让你非常容易的实现一个队列的需求，也可以做为锁来解决资源的竞争

11. 

### 3. 有哪些尝鲜者呢？

>  Go语言毕竟出来也有些年头了，但市面上用她的人却少。可还是少不了一些敢于尝试的人的。

据go群里消息以及一些技术分享会议所知，还是有不少人尝试用它来开发的。
- [七牛云](http://www.qiniu.com/)
- [京东商城](http://www.jd.com/)
- [网易]()开发cdn
- [360]()消息推送系统
- [百度]()bfe系统
- ...

> 什么公司用什么语言并没有什么，重要的是用它解决了什么问题。且深知用它会给我们带了什么问题。


### 4. topic

- 语法入门
- 一个简单的http程序
- 利用通道做一个消息产生和消费
- 结构体的代码展示
- 接口的应用编程
- socket Tcp
- 优秀的开源项目

http://tour.golangtc.com/welcome/
#### 4.1 语法入门

```go
package main //包名，执行程序用main

import (
	f "fmt" //为这个包取个别名
	_ "os"  //只是调用这个包的init，并不引用包里的东西
)

// 定义一个整型常量
const a int = 20

/*
const(
	CONST_A = "ABCD"
	CONST_B = 123
	CONST_C = []byte('lajf')
)
*/

// 指定数据类型并初始化
var g_b int = 10

// 类型推导
var auto = 30

/*
var(
    var_a = 10
	var_b = 'abcd'
	var_c = new(int)
	var_d = make(map[int]int)
)
*/

var (
	E_RED = iota
	E_YELLOW
	E_ORANGE
	E_GREEN
)

//优先main函数执行，一个包可以有多个
func init() {
	f.Println("hello g_b %v", g_b)
}

func main() {
	f.Println("Hello, 世界")
	f.Println("a is %d", a)
	for i := 0; i < a; i++ {
		f.Println("i = %d", i)
	}
}

```

#### 4.2 一个简单的http程序

```golang

package main

import (
	"io"
	"net/http"
	"log"
)

// hello world, the web server
func HelloServer(w http.ResponseWriter, req *http.Request) {
	io.WriteString(w, "hello, world!\n")
}

func main() {
	http.HandleFunc("/hello", HelloServer)
	err := http.ListenAndServe(":12345", nil)
	if err != nil {
		log.Fatal("ListenAndServe: ", err)
	}
}
```

#### 4.3 利用通道做一个消息产生和消费

```golang

```

#### 4.4 接口的应用编程


#### 4.5 socket 


#### 4.6 优秀的开源项目

