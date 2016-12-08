#coding:utf-8
#!/usr/bin/python

import sys
import  smtplib
from    email.mime.text import MIMEText
from    email.header import Header

reload(sys)
sys.setdefaultencoding('utf8')

#配置
ProjectName = "天网三国"            #项目名

#邮件列表(接收者)
mailto= [
    "oscan@61games.hk",
    "andy@61games.hk",
]

#发送者
mail_host       = "smtp.qq.com"
mail_user       = "849397833"
mail_pass       = "hoqkmawbbfpdbgah" #ssl认证
mail_postfix    = "qq.com"

fileName = sys.argv[1]
content= "服务器发现错误" + fileName + "\n\t" + sys.argv[2]

me = "%s<"%(Header('《' + ProjectName + '》服务器警告邮件','utf-8')) + mail_user + "@" + mail_postfix+">"
msg = MIMEText(content, 'plain', 'utf-8')
msg['Subject'] = '错误报告'
msg['From'] = me
msg['To'] = ";".join(mailto)
msg["Accept-Language"]="zh-CN"
msg["Accept-Charset"]="ISO-8859-1,utf-8"
s = smtplib.SMTP()
s.connect(mail_host)
s.starttls()
s.login(mail_user,mail_pass)
s.sendmail(me, mailto, msg.as_string())
s.close()
