#!/bin/bash


# Script de lancement d'un serveur Node.js pour NaturaPass.com
# Auteur: Vincent Valot
# Société: e-Conception

BASEDIR=$(cd $(dirname $0);echo $PWD)

BASEDIR=${BASEDIR}/..
PID_FILE=${BASEDIR}/web/js/app/server.pid

method=""
env="dev"
keepControl=0
port=3000

function start {
    INUSE=`lsof -i:${port} -t`

    if [[ -n ${INUSE} ]]; then
        echo '>> A process is already listening on port '${port}
        exit -1
    else
        filename="${BASEDIR}/web/js/app/server"

        if [ "$keepControl" -eq 1 ]; then
            node ${BASEDIR}/web/js/app/server.js
        else
            NODE_ENV=${env} SOCKET_PORT=${port} nohup node ${BASEDIR}/web/js/app/server.js > /dev/null 2>&1 &

            touch ${PID_FILE}
            echo $! > ${PID_FILE}
        fi

        echo '>> Node server started'
    fi
}

function stop {
    kill -9 `lsof -i:${port} -t`
    echo Node server stopped
}

function usage {
    echo 'Usage ./nodeserver.sh (start|stop|restart) [--keep-control]'
}

echo ">> Node.js / Socket.io Server script"

if [ "$#" -lt 1 ]; then
    usage
fi

method=$1
shift

while (( "$#" )); do
    case $1 in
        '--keep-control')
        keepControl=1
        ;;
        '--env=dev')
        env='dev'
        ;;
        '--env=prod')
        env='prod'
        ;;
    esac

    shift;
done

case ${method} in
    'start')
    start
    ;;
    'stop')
    stop
    ;;
    'restart')
    stop
    echo Now trying to start
    start
    ;;
    *)
    usage
    exit -1
    ;;
esac

exit 0