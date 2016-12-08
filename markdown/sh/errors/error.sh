#!/bin/bash

#the log file prefix with format(prefix.20161111)
PREFIX=(
    login \
    game  \
    center \
)

CUR_DIR=$(dirname $(readlink -f $0))

## help func
msg() {
    printf '%b\n' "$1" >&2
}

success() {
    if [ "$ret" -eq '0' ]; then
        msg "\33[32m[✔]\33[0m ${1}${2}"
    fi
}

error() {
    msg "\33[31m[✘]\33[0m ${1}${2}"
    exit 1
}

FILTER_MSG=(
    "invalid client" \
    "errorStr"  \
)

#发送邮件✘
sendMail(){
    send=""
    len=${#FILTER_MSG[@]}
    for ((i=0; i<$len; i++)); do 
        [ -n "$send" ] && break
        send=`echo "$2" | grep "${FILTER_MSG[i]}"`
    done

    echo "send is $send"

    if [ -z "$send" ]; then
        echo "error msg is $2"
        python $CUR_DIR/mail.py "$1" "$2"
        ret=$?
        if [ "$ret" -ne '0' ]; then
            error $1 $2
        fi
    fi
    #mail -s "三国bug" $m "文件名:$1 行号:$2 \n\t $3"
}

#检查参数
if [[ -z "$1" || ! -d "$1" ]]; then
    error "$1 is null or directory not exist"
fi

DIR=${1%/}
DATE=$(date +%Y%m%d)

for file in ${PREFIX[@]}
do
    filename="$file.$DATE.log"
    filepath="$DIR/$filename"
    msg "begin at:  $(date +%s)"
    #得到上次出错的行号
    [ -f "/tmp/$file" ] && line=`cat /tmp/$file`
    [ -z "$line" ] && line=0 
    if [ -f "$filepath" ]; then
        last=`LU_CALL=C egrep "error|traceback" -n $filepath |tail -1`
        echo "has error? $last"
        last=`echo $last|awk -F ":" '{print $1}'`
        #如果出错最后的行号和上次出现的行号不同则表示有新的错误
        if [[ -n "$last" && -n "$line" &&  ! "$line" -eq "$last" ]];then
            #得到出现该错误及后10行
            emsg=`LU_CALL=C egrep -n "error|traceback" $filepath -A 15 -B 1 | egrep "^$last" -A 15 -B 1`
            sendMail "$filepath" \
                     "$emsg"
            echo "$last" > "/tmp/$file"
        fi
    else
        msg "$filepath not exist"
    fi
    msg "end at $(date +%s)"
done
