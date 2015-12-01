var httpPort    = 8080;

var io = require( 'socket.io' ).listen(httpPort);

io.sockets.on('connection', function(socket) {

    socket.on('login', function(data) {

        socket.userid   = data.userid;
        socket.room     = data.room;
        socket.photo    = data.photo;
        socket.fullname = data.fullname;

        // join the room
        socket.join( data.room );

        io.in(socket.room).emit("online",
            {
                userid   : socket.userid,
                fullname : socket.fullname
            }
        );
    });

    socket.on('disconnect', function() {

        // leave the room
        socket.leave(socket.room);

        io.in(socket.room).emit("offline",
            {
                userid   : socket.userid,
                fullname : socket.fullname
            }
        );
    });

	socket.on('message_to_server', function(data) {

        var messageText = data["message"];
        messageText = messageText.split('&').join('&amp;');
        messageText = messageText.split('<').join('&lt;');
        messageText = messageText.split('>').join('&gt;');

        io.in(socket.room).emit("message_to_client",
			{
                userid:   data["userid"],
				message:  messageText,
				photo:    data["photo"],
                fullname: data["fullname"]
			}
		);
	});
});

console.log('Start on port: '+httpPort);
