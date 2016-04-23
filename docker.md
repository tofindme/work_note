


# docker实践

> docker是用google开源语言golang开发的一款类似vm这样的管理容器的开源软件，他的优势在于不像vm一样启动一个虚拟机需要等待漫长的时间，而对docker而言只需要简单的启动一个image就相当于运行一个独立的container。各个container是相互独立的。起停一个container也是相对方便和快速的


*此笔记记录阅读docker官网并实践操作所记*

### 1 环境准备

- 下载安装centos7.2镜像虚拟机
- [安装docker](http://dockone.io/article/1059)
- 启动docker `systemctl start docker`

> docker版本现在到了1.10了，需要linux 3.10的内核，所以下载的centos7来操作


**服务启动脚本在 `/usr/lib/systemd/system` 目录下**

### 2 Concept

- [Understand images & container](https://docs.docker.com/linux/step_two/)
- [Docker Volume](http://cloud.51cto.com/art/201501/463143.htm)

------




### 3 Network

- [container网张](https://docs.docker.com/engine/userguide/networking/dockernetworks/)


- [ ] 支持以 PDF 格式导出文稿
- [ ] 改进 Cmd 渲染算法，使用局部渲染技术提高渲染效率
- [x] 新增 Todo 列表功能
- [x] 修复 LaTex 公式渲染问题
- [x] 新增 LaTex 公式编号功能




