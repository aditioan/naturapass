/**
 * Created by vincentvalot on 24/07/14.
 */
var log4js = require('log4js');

log4js.configure({
    appenders: [
        {type: 'console'},
        {
            type      : 'file',
            filename  : __dirname + '/../../../app/logs/socket-io.log',
            maxLogSize: 20480,
            backups   : 3
        }
    ]
});

var logger = log4js.getLogger();

process.on('SIGINT', function () {
    logger.info("Caught interrupt signal");
    process.exit();
});

var app = require('express')();
var http = require('http').Server(app);
var server = require('http').createServer();
var io = require('socket.io').listen(server);
var port  = process.env.SOCKET_PORT || 3000;

server.listen(port, 'localhost')
    .on('error', function (err) {
        if (err.code === 'EADDRINUSE') {
            logger.fatal("The port " + port + " is already in use.");
            process.exit();
        }
    });

/**
 *  Used to parse cookie
 */
function parse_cookies(_cookies) {
    var cookies = {};

    _cookies && _cookies.split(';').forEach(function (cookie) {
        var parts = cookie.split('=');
        cookies[parts[0].trim()] = (parts[1] || '').trim();
    });

    return cookies;
}
var clients = [];

logger.info('Socket.io started on', port);

if (process.env.NODE_ENV == 'prod') {
    logger.setLevel('INFO');
    logger.info('PRODUCTION MODE');
} else {
    logger.setLevel('TRACE');
    logger.info('DEVELOPMENT MODE');
}

io.sockets.on('connection', function (socket) {
    logger.trace('[' + socket.id + '] ' + 'CONNECTION');

    socket.on('npevent-user:connected', function (user) {
        if (user && user.usertag) {

            if (!clients.hasOwnProperty(user.usertag)) {
                clients[user.usertag] = [];
            }

            if (clients[user.usertag].indexOf(socket.id) == -1) {
                clients[user.usertag].push(socket.id);
            }
            //for (usertag in clients) {
            //    logger.trace('array level usertag ' + usertag);
            //    if (clients[usertag] instanceof Array) {
            //        for (index in clients[usertag]) {
            //            logger.trace('array level socket ' + clients[usertag][index]);
            //        }
            //    }
            //}

            logger.trace('[' + socket.id + '] ' + user.usertag + ' has logged in');
        }
    });

    /**
     * API: Réception d'une notification
     */
    socket.on('api-publication:processed', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-publication:processed');

        response.receivers.forEach(function (receiver) {
            if (clients.hasOwnProperty(receiver)) {
                for (index in clients[receiver]) {
                    io.to(clients[receiver][index]).emit('npevent-notification:incoming', response.data);
                    io.to(clients[receiver][index]).emit('npevent-publication:processed', response.data.extra_data.publication);
                }
            }
        });
    });

    /**
     * Salon: Ajout d'un utilisateur dans un salon
     */
    socket.on('npevent-lounge:join', function (lounge) {
        logger.trace('[' + socket.id + '] ' + 'npevent-lounge:join');

        socket.join('lounge:' + lounge);
    });

    /**
     * API: Réception d'une invitation d'ami
     */
    socket.on('api-friendship:incoming', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-friendship:incoming');

        if (clients.hasOwnProperty(response.receivers[0])) {
            for (index in clients[response.receivers[0]]) {
                io.to(clients[response.receivers[0]][index]).emit('npevent-invitation:incoming', response.data);
            }
        }
    });

    /**
     * API: Réception d'une notification
     */
    socket.on('api-notification:incoming', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-notification:incoming');

        response.receivers.forEach(function (receiver) {
            if (clients.hasOwnProperty(receiver)) {
                for (index in clients[receiver]) {
                    io.to(clients[receiver][index]).emit('npevent-notification:incoming', response.data);
                }
            }
        });
    });

    /**
     * Chat: Ajout d'un utilisateur dans une conversation
     */
    socket.on('npevent-chat-message:join', function (chat) {
        logger.trace('[' + socket.id + '] ' + 'npevent-chat-message:join');

        socket.join('chat:' + chat);
    });

    /**
     * live: Ajout d'un utilisateur dans un live agenda
     */
    socket.on('npevent-map-live:join', function (hunt) {
        logger.trace('[' + socket.id + '] ' + 'npevent-map-live:join');

        socket.join('map-live:' + hunt);
    });

    /**
     * API: Réception d'une message
     */
    socket.on('api-chat-message:incoming', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-chat-message:incoming');
        response.receivers.forEach(function (receiver) {
            if (clients.hasOwnProperty(receiver)) {
                for (index in clients[receiver]) {
                    io.to(clients[receiver][index]).emit('npevent-chat-message:incoming', response.data);
                }
            }
        });
        io.to('chat:' + response.pool).emit('npevent-chat-message:incoming', response.data);
    });

    /**
     * API: changement de la géolocalisation sur les salons
     */
    socket.on('api-lounge:geolocation', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-lounge:geolocation');

        io.to('lounge:' + response.pool).emit('npevent-lounge:geolocation', response.data);
    });

    /**
     * API: changement de l'admin d'un membre sur les salons
     */
    socket.on('api-lounge:user-admin', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-lounge:user-admin');

        io.to('lounge:' + response.pool).emit('npevent-lounge:user-admin', response.data);
    });

    /**
     * API: changement des droits d'un salon
     */
    socket.on('api-lounge:change-allow', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-lounge:change-allow');

        response.receivers.forEach(function (receiver) {
            if (clients.hasOwnProperty(receiver)) {
                for (index in clients[receiver]) {
                    logger.trace('receiver : ' + clients[receiver][index]);
                    io.to(clients[receiver][index]).emit('npevent-lounge:change-allow', response.data);
                }
            }
        });
        // io.to('lounge:' + response.pool).emit('npevent-lounge:change-allow', response.data);
    });

    /**
     * API: changement des droits d'un groupe
     */
    socket.on('api-group:change-allow', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-group:change-allow');

        response.receivers.forEach(function (receiver) {
            if (clients.hasOwnProperty(receiver)) {
                for (index in clients[receiver]) {
                    logger.trace('receiver : ' + clients[receiver][index]);
                    io.to(clients[receiver][index]).emit('npevent-group:change-allow', response.data);
                }
            }
        });
        // io.to('lounge:' + response.pool).emit('npevent-lounge:change-allow', response.data);
    });

    /**
     * API: Réception d'un message sur les salons
     */
    socket.on('api-lounge:message', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-lounge:message');

        io.to('map-live:' + response.pool).emit('npevent-lounge:message', response.data);
        io.to('lounge:' + response.pool).emit('npevent-lounge:message', response.data);
    });

    /**
     * API: Réception d'un message sur les salons
     */
    socket.on('api-lounge:publication', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-lounge:publication');
        io.to('map-live:' + response.pool).emit('npevent-lounge:publication', response.data);
        io.to('lounge:' + response.pool).emit('npevent-lounge:publication', response.data);
    });

    /**
     * API: changement de participation d'un membre sur les salons
     */
    socket.on('api-lounge:participation', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-lounge:participation');

        io.to('lounge:' + response.pool).emit('npevent-lounge:participation', response.data);
    });

    /**
     * API: changement du status silencieux d'un membre sur les salons
     */
    socket.on('api-lounge:quiet', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-lounge:quiet');

        io.to('lounge:' + response.pool).emit('npevent-lounge:quiet', response.data);
    });

    /**
     * API: changement de participation d'un non membre sur les salons
     */
    socket.on('api-lounge:participationnotmember', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-lounge:participationnotmember');

        io.to('lounge:' + response.pool).emit('npevent-lounge:participationnotmember', response.data);
    });

    /**
     * API: Ajout d'un non membre sur les salons
     */
    socket.on('api-lounge:addnotmember', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-lounge:addnotmember');

        io.to('lounge:' + response.pool).emit('npevent-lounge:addnotmember', response.data);
    });

    /**
     * API: Suppression d'un non membre sur les salons
     */
    socket.on('api-lounge:removenotmember', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-lounge:removenotmember');

        io.to('lounge:' + response.pool).emit('npevent-lounge:removenotmember', response.data);
    });

    /**
     * API: Suppression d'un non membre sur les salons
     */
    socket.on('api-lounge:removemember', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-lounge:removemember');

        io.to('lounge:' + response.pool).emit('npevent-lounge:removemember', response.data);
    });

    /**
     * API: Réception d'une mise à jour de géolocalisation d'un utilisateur
     */
    socket.on('api-lounge:subscriber-geolocation', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-lounge:subscriber-geolocation');

        io.to('lounge:' + response.pool).emit('npevent-lounge:subscriber-geolocation', response.data);
    });

    /**
     * Salon: Ajout d'un utilisateur dans un groupe
     */
    socket.on('npevent-group:join', function (group) {
        logger.trace('[' + socket.id + '] ' + 'npevent-group:join');

        socket.join('group:' + group);
    });

    /**
     * API: changement de l'admin d'un membre sur les groupes
     */
    socket.on('api-group:user-admin', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-group:user-admin');

        io.to('group:' + response.pool).emit('npevent-group:user-admin', response.data);
    });

    /**
     * API: Réception d'un message sur les groupes
     */
    socket.on('api-group:message', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-group:message');

        io.to('group:' + response.pool).emit('npevent-group:message', response.data);
    });

    /**
     * API: Suppression d'un membre sur les groupes
     */
    socket.on('api-group:removemember', function (response) {
        logger.trace('[' + socket.id + '] ' + 'api-group:removemember');

        io.to('group:' + response.pool).emit('npevent-group:removemember', response.data);
    });

    socket.on('disconnect', function () {
        for (usertag in clients) {
            if (clients[usertag] instanceof Array) {
                for (index in clients[usertag]) {
                    if (clients[usertag][index] == socket.id) {
                        delete clients[usertag][index];
                    }
                }
            }
        }
        logger.trace('[' + socket.id + '] ' + 'DISCONNECTION');
    });
})
;
