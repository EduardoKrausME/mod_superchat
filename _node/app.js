// Node JS port
var httpPort    = 8080;

// Starts listening port
var io = require( 'socket.io' ).listen(httpPort);

/**
 * Each new connection is treated here
 */
io.sockets.on('connection', function(socket) {

    /**
     * Login
     *
     * * * * * You have to add security
     */
    socket.on('login', function(data) {

        socket.userid   = data.userid;
        socket.room     = data.room;
        socket.photo    = data.photo;
        socket.fullname = data.fullname;

        // join the room
        socket.join( data.room );

        // Send message to all connected warning connection
        io.in(socket.room).emit("online",
            {
                userid   : socket.userid,
                fullname : socket.fullname
            }
        );
    });

    /**
     * When someone leaves the room
     */
    socket.on('disconnect', function() {

        // leave the room
        socket.leave(socket.room);

        // Send message to all connected warning output
        io.in(socket.room).emit("offline",
            {
                userid   : socket.userid,
                fullname : socket.fullname
            }
        );
    });

    /**
     * Receive and treat the Chat messages
     */
	socket.on('message_to_server', function(data) {

        var messageText = data["message"];
        messageText = messageText.split('&').join('&amp;');
        messageText = messageText.split('<').join('&lt;');
        messageText = messageText.split('>').join('&gt;');

        // Sends the message to all connected persons
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

// Debug message
console.log('Start on port: '+httpPort);
