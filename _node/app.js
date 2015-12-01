var httpPort    = 8080;

var io = require( 'socket.io' ).listen(httpPort);

io.sockets.on('connection', function(socket) {

    socket.on('login', function(data) {

        socket.userid   = data.userid;
        socket.room     = data.room;
        socket.photo    = data.photo;
        socket.fullname = data.fullname;


        //console.log("Join on ROOM " +socket.room);

        socket.join( data.room );

        io.in(socket.room).emit("online",
        //socket.broadcast.to(socket.room).emit("online",
            {
                userid : data.userid
            }
        );
    });

    socket.on('disconnect', function() {
        // leave the room
        socket.leave(socket.room);

        //console.log("leave on ROOM " +socket.room);

        io.in(socket.room).emit("offline",
        //socket.broadcast.to(socket.room).emit("offline",
            {
                userid : socket.userid
            }
        );
    });

	socket.on('message_to_server', function(data) {

        var messageText = data["message"];
        messageText = messageText.split('&').join('&amp;');
        messageText = messageText.split('<').join('&lt;');
        messageText = messageText.split('>').join('&gt;');

        //console.log(messageText);
        //console.log("message on ROOM " +socket.room);

        io.in(socket.room).emit("message_to_client",
        //socket.broadcast.to(socket.room).emit("message_to_client",
			{
                userid:   data["userid"],
				message:  messageText,
				photo:    data["photo"],
                fullname: data["fullname"]
			}
		);
	});
    socket.on('server_add_user', function(data) {
        io.in(socket.room).emit("client_add_user",
        //socket.broadcast.to(socket.room).emit("client_add_user",
            {
                userid   : data["userid"],
                fullname : data["fullname"]
            }
        );
    });
});

console.log('Start on port: '+httpPort);
