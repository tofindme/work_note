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

