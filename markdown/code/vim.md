# vim游记

> 一直听说vip是一个牛逼的编辑器，指尖上的飞舞，很多人都喜欢他。最近也玩上了vim,所以也用来记录下常用的vim，以便后续来快速查找,我用的是spf13这个vim工具，安装和卸载非常方便。

[spf13-vim](https://github.com/spf13/spf13-vim)

spf13 在当前用户目录的配置都是带.local文件的，让用户可选性非常方便

w 按单词向前跳,前面可以跟数字，代表前进的单词数
b 按单词向后跨省

dw 代表删除单词 中间可跟数字 
dd 删除整行,前面跟数字是在当前光标行删除的行数
dG 从当前光标删除到文档最后
d$ 删除光到到行尾

yw 拷贝一个单词
yy 拷贝整行
yG 拷贝当前光标到整行

ctrl+z 代表当前编辑的vim到后台运行
fg     代表后台运行的vim再到前台来
bg     与fg相反

set nospell 是去掉默认高亮的单词，看起来比较刺眼
colorscheme  monokain 此主题是和sublime默认的主题一样比较喜欢



vs          是水平分割窗口
ctrl+w      打开目录导航
shift+#     查找当前高亮的字符串
help map    对某个vi命令的帮助文档

help key-notation 代表按键的意思说明


nmap <C-S> :w<CR>   代表ctrl+s组合键代表保存

gg=G  按`v`选中需要对齐的然后`=`    对齐命令

ctrl+v  进入列编辑模式

## vim 宏

> 宏功能实现是所一些vim命令记录到寄存器，然后读取出来执行可以，使用方法是

q + 保存寄存器地址的字母(a-z) 开启宏记录

vim 命令

q 退出宏记录

数字 + @ + 保存寄存器地址的字母

数字代表多次执行这个宏


