
####

61.91.161.217    go.googlesource.com
61.91.161.217    golang.org
61.91.161.217    www.golang.org
61.91.161.217    blog.golang.org
61.91.161.217    play.golang.org
61.91.161.217    tip.golang.org
61.91.161.217    tour.golang.org
74.125.28.14    google.golang.org




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
- testing库
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
    "sync/atomic"
    "fmt"
)

var num int32 = 0

// hello world, the web server
func HelloServer(w http.ResponseWriter, req *http.Request) {
    atomic.AddInt32(&num, 1)
    io.WriteString(w, fmt.Sprintf("你是第%d个访问者\n",num))
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
package main

import (
    "fmt"
    "time"    
)

// 代表只写
func Producer(ch chan<- int){
    i := 1
    for{
        time.Sleep(time.Second)
        ch <- i
        i++
        if ( i == 3 ){
            close(ch)
            break
        }
    }
}


// 代表只读
func Consumer(ch <-chan int){
    for data := range ch {
        fmt.Printf("out put is %v\n", data)
    }
}

func main(){
    channel := make(chan int, 1)
    
    go Producer(channel)
    
    go Consumer(channel)
    
    time.Sleep(10 * time.Second)
}



-----

//一个select的例子
package main

import (
    "fmt"
    "time"
    "math/rand"
)

func init(){
    rand.Seed(42)
}

func GetValue(ch chan<- int){
    i := 0
    for{
        ch <- rand.Intn(20)
        i++
        if (i == 10){
            close(ch)
            break;
        }
    }
}


func main()  {
    ticker := time.NewTicker(time.Second * 5)
    sig := make(chan int)
    
    go GetValue(sig)
    
    exit := false
    
    for {
        select{
            case signal,err := <- sig: // 关闭了能过，err判断
                if ( err == false){
                    exit = true
                    break;
                }
                fmt.Printf("select signal is %d err is %v\n", signal, err)
            case <-ticker.C:
                exit = true
                break;
        }
        
        if (exit == true){
            break;
        }        
    }
}


```

#### 4.4 接口的应用编程

```golang


```


#### 4.5 socket 

```golang
ln, err := net.Listen("tcp", ":8080")
if err != nil {
    // handle error
}
for {
    conn, err := ln.Accept()
    if err != nil {
        // handle error
    }
    go handleConnection(conn)
}

```


#### 4.6 testing库

> go提供了丰富的测试库，可以针对某个函数测试，也可以做性能测试，go自带的sdk库基本都是带*_test.go的，使用起来也比较方便


```golang

// 单元测试
package main

import(
    "testing"
    "encoding/json"
    "os"
    "fmt"
)

func TestMarshal(t *testing.T) {
    fmt.Println("in TestMarshal do some logic")
    type Json struct {
        Name string `json:"name"`
        Age  int `json:"age"`
    }
    
    person := Json{Name:"yibin", Age:25}
    
    b, err := json.Marshal(person)
    if err != nil {
        t.Error("marshal failed error:", err)
    }
    os.Stdout.Write(b)
}


func TestUnmarshal(t *testing.T) {
    fmt.Println("in TestMarshal do some logic")
    str := `{"name":"yibin", "age" : 25}`
    type Json struct {
        Name string `json:"name"`
        Age int     `json:"age"`
    }
    var des Json
    err := json.Unmarshal([]byte(str), &des)
    if err != nil {
        fmt.Println("error:", err)
    }
    fmt.Printf("person is %v \n", des)    
}

// go test


//性能测试
package main

import(
    "testing"
)

func test(x int) int {
    return x * 2
}


func BenchmarkTest(b *testing.B){
    for i := 0; i < b.N; i++ {
        _ = test(i)
    }
}


func BenchmarkAnonymous(b *testing.B) {
    for i := 0; i < b.N; i++ {
        _ = func(x int) int{
            return x * 2
        }(i)
    }
}


func BenchmarkClosure(b *testing.B){
    for i := 0; i < b.N; i++ {
        _ = func() int{
            return i * 2
        }()
    }
}

// go test -v -bench . -benchmem


```


#### 4.7 优秀的开源项目



