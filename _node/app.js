console.log( 'v0.4.2' );

// Node JS port
var httpPort = 8080;

// Starts listening port
var io = require( 'socket.io' ).listen( httpPort );

var historyUrl          = {};
var historyMessages     = {};
var historyNumMessages  = 0;

/**
 * Each new connection is treated here
 */
io.sockets.on( 'connection', function( socket )
{
    /**
     * Login
     */
    socket.on( 'login', function( data )
    {
        var statusDomain = domainIsValid( socket.handshake.headers );
        if( !statusDomain )
        {
            socket.emit( 'logof', 'Domain not allow ' );
            socket.leave( socket.room );
            return;
        }


        if( historyUrl[ data.room ] == undefined )
        {
            historyUrl[ data.room ] = data.auth_url;
            historyMessages[ data.room ] = {};
        }

        var request = require( "request" );

        var url = data.auth_url + "json-auth.php?sessionid=" + data.session_id + '&sessionname=' + data.session_name;
        request( {
            url: url,
            json: true
        }, function ( error, response, reply ) {

            if ( !error && response.statusCode === 200 )
            {
                socket.room     = data.room;
                socket.userid   = reply.userid;
                socket.fullname = reply.fullname;
                socket.photo    = reply.photo;

                // join the room
                socket.join( data.room );
                socket.emit( 'joinroom', reply );

                sendAllUserOnline( data.room );

                //
                var history  = historyMessages[ data.room ];
                historyMessages[ data.room ] = {};

                if( Object.keys(history).length )
                    saveHistoryMoodle( historyUrl[ data.room ], history, data.room )
            }
            else{
                socket.emit( 'logof', reply );
                socket.leave( socket.room );
            }
        } );
    } );

    /**
     * When someone leaves the room
     */
    socket.on( 'disconnect', function()
    {
        // leave the room
        socket.leave( socket.room );

        sendAllUserOnline( socket.room );
    } );

    /**
     * Receive and treat the Chat messages
     */
	socket.on( 'message_to_server', function( data )
    {
        var messageText = data["message"];
        messageText = messageText.split( '&' ).join( '&amp;' );
        messageText = messageText.split( '<' ).join( '&lt;' );
        messageText = messageText.split( '>' ).join( '&gt;' );

        var date = new Date();
        historyMessages[ socket.room ][ historyNumMessages++ ] = {
            message   : messageText,
            userid    : socket.userid,
            timestamp : parseInt( date.getTime() / 1000 )
        };

        // Sends the message to all connected persons
        io.in( socket.room ).emit( "message_to_client",
			{
                message  : messageText,
                userid   : socket.userid,
                photo    : socket.photo,
                fullname : socket.fullname
            }
		 );
	} );
} );

function sendAllUserOnline( room )
{
    var onlineUsers = [];
    for ( var _id in io.sockets.sockets )
    {
        var _socket = io.sockets.sockets[ _id ];
        if( _socket.room == room )
            onlineUsers.push( _socket.userid );
    }
    io.in( room ).emit( 'allOnnlineUsers', onlineUsers );
}

function domainIsValid( headers )
{
    // get domain from referer
    var domainReferer = headers.referer.split( '/' )[2];

    fs = require( 'fs' );
    var resultDomains = fs.readFileSync( 'allowed-domains.txt', 'utf8' );
    var listDomains = resultDomains.split( "\n" );

    for( var key in listDomains )
    {
        var lineDomain = listDomains[ key ].trim();

        // If comments or blank line
        if( lineDomain[0] == '#' || lineDomain.length == 0 )
            continue;

        // It is an asterisk
        if( lineDomain == '*' )
            return true;

        // It begins with asterisk
        if( lineDomain[0] == '*' )
        {
            lineDomain = lineDomain.slice( 1 );
            var reg = new RegExp( lineDomain + "$" );
            if( reg.test( domainReferer ) )
                return true;
        }
        // It is equal
        if( domainReferer == lineDomain )
            return true;
    }

    console.log( domainReferer + ' Domain not Found' );
    return false;
}

function saveHistoryMoodleinterval()
{
    setTimeout( saveHistoryMoodleinterval, 60000 );

    historyNumMessages = 0;
    var tmpHistoryMessages = historyMessages;
    historyMessages = {};

    for( var room1 in tmpHistoryMessages )
        historyMessages[ room1 ] = {};

    for( var room in tmpHistoryMessages )
    {
        var history  = tmpHistoryMessages[ room ];
        var auth_url = historyUrl[ room ];

        if( Object.keys(history).length )
            saveHistoryMoodle( auth_url, history, room )
    }
}
saveHistoryMoodleinterval();

function saveHistoryMoodle( auth_url, historyMessages, room )
{
    //console.log(room);
    var request = require( 'request' );

    var options = {
        uri    : auth_url + 'json-save.php',
        method : 'POST',
        json   : true,
        qs     : {
            history : JSON.stringify(historyMessages),
            room    : room
        }
    };

    request( options, function ( error, response, body ){
        //console.log(error);
        //console.log(body);
    });
}



// Debug message
console.log( 'Start on port: '+httpPort );
