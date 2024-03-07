var ENV = require('node-env-file')(__dirname + '/.env');
var fs = require( 'fs' );
var app = null;
if (ENV.SSL_NODE == 'true') {
    app = require('https').createServer({
            key: fs.readFileSync(ENV.SSL_KEY),
            cert: fs.readFileSync(ENV.SSL_CRT),
        }
    );
} else {
    app = require('http').createServer();
}
var Redis = require('ioredis');

var redis = new Redis({
    port: ENV.REDIS_PORT,   // Redis port
    host: ENV.REDIS_HOST,   // Redis host
    db: ENV.REDIS_DATABASE
});
var jwt = require('jsonwebtoken');

var io = require('socket.io')(app);

//set def value

app.listen(ENV.BROADCAST_PORT, function(){
    console.log('Listening on Port ' + ENV.BROADCAST_PORT);
});

io.use(function(socket, next) {
    var decoded;
    try {
        decoded = jwt.verify(socket.handshake.query.jwt, ENV.JWT_SECRET);
    } catch (err) {
        console.error(err);
        next(new Error('Invalid token!'));
    }

    if (decoded && typeof decoded.sub == "number") {
        // everything went fine - save userId as property of given connection instance
        socket.userId = decoded.sub; // save user id we just got from the token, to be used later
        next();
    } else {
        // invalid token - terminate the connection
        next(new Error('Invalid token!'));
    }
});
io.on('connection', function(socket) {

    socket.on('change-project', function (data) {
        if (typeof data.project_id != 'undefined') {

            if (typeof socket.room != 'undefined') {
                socket.leave(socket.room);
            }

            socket.room = 'user.' + data.project_id + '.' + socket.userId;
            socket.join(socket.room);
        }
    });

    socket.on('disconnect', function () {
        if (typeof socket.room != 'undefined') {
            socket.leave(socket.room);
        }
    });
});
redis.psubscribe('*', function(err, count) {
    //
});
redis.on('pmessage', function(subscribed, channel, message) {
    message = JSON.parse(message);
    io.to(channel).emit(message.data.channel, message.data.data);
});