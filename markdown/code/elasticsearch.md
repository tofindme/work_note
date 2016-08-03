# elasticsearch

> elasticsearch是一个强大的基于全文检索的搜索引擎，它能很轻松的对PB级别的的内容进行快速的查询，且它是可平行扩展的分布式搜索引擎。

以前在公司圆通就用到了这个搜索工具，把mysql不同表的数据同步到elasticsearch不同的index里面，然后实现快速的查找功能。同样是支持一次查询多个index。一个index下面可以拥有不同的type，type又可以算定义mapping，elasticsearch提供了非常方便的REST FUL的API方式进行数据检索.后来在深入了解他的实现原理中了解的更加深入。

elasticsearch是基于Java的Lucene来实现数据索引的。而它实现的原理是利用了倒排索引，倒排索引的概念大概就是找关键词，不同的语言用不同的分词器被分割成多个关键词。





Lucene是一个高性能的java全文检索工具包，它使用的是倒排文件索引结构。该结构及相应的生成算法如下：
　　
　　0）设有两篇文章1和2
　　文章1的内容为：Tom lives in Guangzhou,I live in Guangzhou too.
　　文章2的内容为：He once lived in Shanghai.
　　
　　1)由于lucene是基于关键词索引和查询的，首先我们要取得这两篇文章的关键词，通常我们需要如下处理措施
　　a.我们现在有的是文章内容，即一个字符串，我们先要找出字符串中的所有单词，即分词。英文单词由于用空格分隔，比较好处理。中文单词间是连在一起的需要特殊的分词处理。
　　b.文章中的”in”, “once” “too”等词没有什么实际意义，中文中的“的”“是”等字通常也无具体含义，这些不代表概念的词可以过滤掉
　　c.用户通常希望查“He”时能把含“he”，“HE”的文章也找出来，所以所有单词需要统一大小写。
　　d.用户通常希望查“live”时能把含“lives”，“lived”的文章也找出来，所以需要把“lives”，“lived”还原成“live”
　　e.文章中的标点符号通常不表示某种概念，也可以过滤掉
　　在lucene中以上措施由Analyzer类完成
　　
　　经过上面处理后
　　 文章1的所有关键词为：[tom] [live] [guangzhou] [live] [guangzhou]
　　 文章2的所有关键词为：[he] [live] [shanghai]
　　
　　2) 有了关键词后，我们就可以建立倒排索引了。上面的对应关系是：“文章号”对“文章中所有关键词”。倒排索引把这个关系倒过来，变成：“关键词”对“拥有该关键词的所有文章号”。文章1，2经过倒排后变成
　　关键词               文章号
　　guangzhou         1
　　he                     2
　　i                        1
　　live                    1,2
　　shanghai            2
　　tom                   1
　　
　　通常仅知道关键词在哪些文章中出现还不够，我们还需要知道关键词在文章中出现次数和出现的位置，通常有两种位置：a)字符位置，即记录该词是文章中第几个字符（优点是关键词亮显时定位快）；b)关键词位置，即记录该词是文章中第几个关键词（优点是节约索引空间、词组（phase）查询快），lucene 中记录的就是这种位置。
　　
　　加上“出现频率”和“出现位置”信息后，我们的索引结构变为：


　　关键词             文章号[出现频率]                     出现位置
　　guangzhou        1[2]                                        3，6
　　he                     2[1]                                       1
　　i                        1[1]                                       4
　　live                    1[2],2[1]                                 2，5，2
　　shanghai            2[1]                                       3
　　tom                   1[1]                                        1
　　
　　以live 这行为例我们说明一下该结构：live在文章1中出现了2次，文章2中出现了一次，它的出现位置为“2,5,2”这表示什么呢？我们需要结合文章号和出现频率来分析，文章1中出现了2次，那么“2,5”就表示live在文章1中出现的两个位置，文章2中出现了一次，剩下的“2”就表示live是文章2中第 2个关键字。
　　
　　以上就是lucene索引结构中最核心的部分。我们注意到关键字是按字符顺序排列的（lucene没有使用B树结构），因此lucene可以用二元搜索算法快速定位关键词。
　　
　　实现时 lucene将上面三列分别作为词典文件（Term Dictionary）、频率文件(frequencies)、位置文件 (positions)保存。其中词典文件不仅保存有每个关键词，还保留了指向频率文件和位置文件的指针，通过指针可以找到该关键字的频率信息和位置信息。
　　
　　 Lucene中使用了field的概念，用于表达信息所在位置（如标题中，文章中，url中），在建索引中，该field信息也记录在词典文件中，每个关键词都有一个field信息(因为每个关键字一定属于一个或多个field)。
　　
　　为了减小索引文件的大小，Lucene对索引还使用了压缩技术。首先，对词典文件中的关键词进行了压缩，关键词压缩为<堉?缀长度，后缀>，例如：当前词为“阿拉伯语”，上一个词为“阿拉伯”，那么“阿拉伯语”压缩为<3，语>。其次大量用到的是对数字的压缩，数字只保存与上一个值的差值（这样可以减小数字的长度，进而减少保存该数字需要的字节数）。例如当前文章号是16389（不压缩要用3个字节保存），上一文章号是16382，压缩后保存7（只用一个字节）。
　　
　　 下面我们可以通过对该索引的查询来解释一下为什么要建立索引。
　　假设要查询单词 “live”，lucene先对词典二元查找、找到该词，通过指向频率文件的指针读出所有文章号，然后返回结果。词典通常非常小，因而，整个过程的时间是毫秒级的。
　　而用普通的顺序匹配算法，不建索引，而是对所有文章的内容进行字符串匹配，这个过程将会相当缓慢，当文章数目很大时，时间往往是无法忍受的。




本文来自CSDN博客，转载请标明出处：http://blog.csdn.net/geekwang/archive/2008/11/29/3410187.aspx是一个高性能的java全文检索工具包，它使用的是倒排文件索引结构。该结构及相应的生成算法如下：
　　现在也是c#的全文检索工具包了，所以都一样的。
　　0）设有两篇文章1和2
　　文章1的内容为：Tom lives in Guangzhou,I live in Guangzhou too.
　　文章2的内容为：He once lived in Shanghai.
　　
　　1)由于lucene是基于关键词索引和查询的，首先我们要取得这两篇文章的关键词，通常我们需要如下处理措施
　　a.我们现在有的是文章内容，即一个字符串，我们先要找出字符串中的所有单词，即分词。英文单词由于用空格分隔，比较好处理。中文单词间是连在一起的需要特殊的分词处理。
　　b.文章中的”in”, “once” “too”等词没有什么实际意义，中文中的“的”“是”等字通常也无具体含义，这些不代表概念的词可以过滤掉
　　c.用户通常希望查“He”时能把含“he”，“HE”的文章也找出来，所以所有单词需要统一大小写。
　　d.用户通常希望查“live”时能把含“lives”，“lived”的文章也找出来，所以需要把“lives”，“lived”还原成“live”
　　e.文章中的标点符号通常不表示某种概念，也可以过滤掉
　　在lucene中以上措施由Analyzer类完成
　　
　　经过上面处理后
　　 文章1的所有关键词为：[tom] [live] [guangzhou] [live] [guangzhou]
　　 文章2的所有关键词为：[he] [live] [shanghai]
　　
　　2) 有了关键词后，我们就可以建立倒排索引了。上面的对应关系是：“文章号”对“文章中所有关键词”。倒排索引把这个关系倒过来，变成：“关键词”对“拥有该关键词的所有文章号”。文章1，2经过倒排后变成
　　关键词               文章号
　　guangzhou         1
　　he                     2
　　i                        1
　　live                    1,2
　　shanghai            2
　　tom                   1
　　
　　通常仅知道关键词在哪些文章中出现还不够，我们还需要知道关键词在文章中出现次数和出现的位置，通常有两种位置：a)字符位置，即记录该词是文章中第几个字符（优点是关键词亮显时定位快）；b)关键词位置，即记录该词是文章中第几个关键词（优点是节约索引空间、词组（phase）查询快），lucene 中记录的就是这种位置。
　　
　　加上“出现频率”和“出现位置”信息后，我们的索引结构变为：


　　关键词             文章号[出现频率]                     出现位置
　　guangzhou        1[2]                                        3，6
　　he                     2[1]                                       1
　　i                        1[1]                                       4
　　live                    1[2],2[1]                                 2，5，2
　　shanghai            2[1]                                       3
　　tom                   1[1]                                        1
　　
　　以live 这行为例我们说明一下该结构：live在文章1中出现了2次，文章2中出现了一次，它的出现位置为“2,5,2”这表示什么呢？我们需要结合文章号和出现频率来分析，文章1中出现了2次，那么“2,5”就表示live在文章1中出现的两个位置，文章2中出现了一次，剩下的“2”就表示live是文章2中第 2个关键字。
　　
　　以上就是lucene索引结构中最核心的部分。我们注意到关键字是按字符顺序排列的（lucene没有使用B树结构），因此lucene可以用二元搜索算法快速定位关键词。
　　
　　实现时 lucene将上面三列分别作为词典文件（Term Dictionary）、频率文件(frequencies)、位置文件 (positions)保存。其中词典文件不仅保存有每个关键词，还保留了指向频率文件和位置文件的指针，通过指针可以找到该关键字的频率信息和位置信息。
　　
　　 Lucene中使用了field的概念，用于表达信息所在位置（如标题中，文章中，url中），在建索引中，该field信息也记录在词典文件中，每个关键词都有一个field信息(因为每个关键字一定属于一个或多个field)。
　　
　　为了减小索引文件的大小，Lucene对索引还使用了压缩技术。首先，对词典文件中的关键词进行了压缩，关键词压缩为<堉?缀长度，后缀>，例如：当前词为“阿拉伯语”，上一个词为“阿拉伯”，那么“阿拉伯语”压缩为<3，语>。其次大量用到的是对数字的压缩，数字只保存与上一个值的差值（这样可以减小数字的长度，进而减少保存该数字需要的字节数）。例如当前文章号是16389（不压缩要用3个字节保存），上一文章号是16382，压缩后保存7（只用一个字节）。
　　
　　 下面我们可以通过对该索引的查询来解释一下为什么要建立索引。
　　假设要查询单词 “live”，lucene先对词典二元查找、找到该词，通过指向频率文件的指针读出所有文章号，然后返回结果。词典通常非常小，因而，整个过程的时间是毫秒级的。
　　而用普通的顺序匹配算法，不建索引，而是对所有文章的内容进行字符串匹配，这个过程将会相当缓慢，当文章数目很大时，时间往往是无法忍受的。