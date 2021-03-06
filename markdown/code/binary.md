# 二进制


> 二进制是个伟大的发明，也就是计算机独有的语言，他的任何一切都是用二进制来表示的，底层的存储都是只有两个数字，也就是1和0来表达的，真的是不得不说，这比任何语言都屌。由于之前对进制是比较陌生的，到现在也是不太能驾轻就熟。所以就写一个md来做个简单的记录


> 小记
以前面试的时候最担心的就是二进制相关的题了，心里也没底，现在仔细的研究了一番并且有实践，妈妈再也不用担心我的二进制了

------

- 程序里面一般我们涉及的进制有最多的十进制以及用的不太多的十六、八、二进制
- 与我们最直接打交道也是我们能看到的数字相关的都是用十进制表示的


程序开发避免不了内存占用这词，平常说一个字符代表一个字节，一个字节占八个bit位，bit位就用0和1来表示的
这里的字符通常指是发明计算机国度的英文字母，中国是的字符是汉字，所以一个字节是存不下来的，所以聪明的中国人同样发明了自己的编码来存下自己的语言，每个字符用几个字节来对应这个汉字，所以我们可以用中文来交流了，要不然我们也是英语八级啦，哈哈哈！


最近在做一个需求，为了节省空间，涉及到用bit位来存储。所以写了以下几个函数

```lua

local pass_bit = 0x01 -- 通关奖励
local full_bit = 0x02 -- 完美通关奖励
local new_bit  = 0x04 -- 是否第一次玩 
local win_bit  = 0x08 -- 关卡首次胜利

-- src 源数字
-- hex 是二进制
-- 位操作需要5.3以上版本才支持
function utils.set_bit( src, hex )
    if type(src) ~= "number" then
        assert(nil, 'utils.set_bit is not number type')
    end    
    
    src = src | hex
    return src    
end

function utils.zero_bit( src, hex)
    if type(src) ~= "number" then
        assert(nil, 'utils.zero_bit is not number type')
    end

    src = src & (~hex)
    return src
end

function utils.bit_set( src, hex)
    if type(src) ~= "number" then
        assert(nil, 'utils.bit_set is not number type')
    end

    return (src & hex) == hex
end

```

----

用十六进制是因为一位十六进制就可以用四位二进制来表示0x0f就是8个bit，二进制就是00001111
0x01代表第1位
0x02代表第2位
0x04代表第3位
0x08代表第4位

所以某位的bit和清除就相当简单了，用一个位运算符即可，当然判断某位是否设置了同样简单,如上面的函数

### 左移右移

计算机内存存储是按字节存储的，无法存储的就会用多个字节来存储，我们在编程里面通常会有int8,int16,int32等不同大小字类型用来存储我们需要的数据。今天在遇到把一个int32或int16用大端或小端存储的时候在想怎么实现的。

```golang

//小端
func (littleEndian) PutUint32(b []byte, v uint32) {
    b[0] = byte(v)
    b[1] = byte(v >> 8)
    b[2] = byte(v >> 16)
    b[3] = byte(v >> 24)
}

//大端
func (bigEndian) PutUint32(b []byte, v uint32) {
    b[0] = byte(v >> 24)
    b[1] = byte(v >> 16)
    b[2] = byte(v >> 8)
    b[3] = byte(v)
}


```

[字节序](http://www.ruanyifeng.com/blog/2016/11/byte-order.html)

因为内存地址是线性增长的，所以不同的计算机按字节的高低的存放顺序可能不同。所以大端模式与地址的增长相反，数字的高位放在低地址，数字的低位放在高地址;小端模式则相反。

所以在位移操作是怎么得到高字节和低字节的。下面以32位做为示例
下面是一个四个字节的表示,先不考虑用数字来表示，依次从左到右是低位到高位

右移:
    高位                           低位
    00000000 00000000 00000000 00000000
    byte(v >> 24) 剩余一个字节，也就是高8位
    byte(v >> 16) 剩余两个字节，但取的值是倒数第二个字节
    byte(v >> 8) 剩余三个字节,但取的值是倒数第三个字节
    byte(v) 取最低位


byte(v)为什么拿到的是低位呢？自己猜想是因为计算机内部是小端模式的原因。