// only execute on start jquery

$( function() {
    var htmlBody            = $( 'body' ),
        chats               = $( "#chats" ),
        usersli             = $( "#users li" ),
        submit              = $( '#submit' ),
        chatfooter          = $( '#chatfooter' ),
        newmessage          = $( '.new-message' ),
        messagePlaceholder  = $( '#message-placeholder' ),
        message             = $( '#message' ),
        server_room         = $( '#server_room' ),
        ultimaMensagemDe    = 0,
        user                = null,
        autoSroll           = true;
        ultimaDataPostada   = getDataAtual();

    // Connects to the server node js
    var socketio;
    try {
        socketio = io.connect( $( '#server_host' ).val() + ":" + $( '#server_port' ).val() );
    } catch ( error ) {
        showError ( 1 );
    }

    // When connecting make:
    socketio.on( 'connect', function()
    {
        var url = document.location.href.split( 'view.php' );
        var authUrl = url[ 0 ];
        socketio.emit( "login",
            {
                auth_url     : authUrl,
                session_id   : $( '#session_id' ).val(),
                session_name : $( '#session_name' ).val(),
                room         : server_room.val()
            }
        );
    });
    socketio.on( 'disconnect', function()
    {
        chatfooter.find( '#message-area' ).hide();
        chatfooter.find( '#wait-area' ).show();
        chatfooter.find( '.status-area-error' ).hide();
    });

    // When you receive a new message do:
    socketio.on( "logof", function ( data )
    {
        showError ( 2, data );
    });

    // User join in Room
    socketio.on( "joinroom", function ( data )
    {
        user = data;

        // Show message área
        chatfooter.find( '#message-area' ).show();
        chatfooter.find( '#wait-area' ).hide();
        chatfooter.find( '.status-area-error' ).hide();

        // Clear message area and set focus
        message.html ( '' ).focus();

        // resize background area
        resizeBackgroundMessage();
    });

    // process list on-line user
    socketio.on( "allOnnlineUsers", function ( allUsers )
    {
        usersli.attr( 'data-on', 0 );
        usersli.find( '.user-status' ).removeClass( 'online' );
        for ( var _id in allUsers )
        {
            var student = $( '#student_' + allUsers[ _id ] );
            student.attr( 'data-on', 1 );
            student.find( '.user-status' ).addClass( 'online' );
        }


        var items = usersli.get();
        items.sort( function( a, b ){
            var orderA  = $( a ).attr( 'data-order' );
            var orderB  = $( b ).attr( 'data-order' );

            var dataonA = $( a ).attr( 'data-on' );
            var dataonB = $( b ).attr( 'data-on' );

            if ( dataonA == dataonB )
            {
                if ( orderA < orderB ) return -1;
                if ( orderA > orderB ) return 1;
                return 0;
            }
            if ( dataonA > dataonB ) return -1;
            if ( dataonA < dataonB ) return 1;
            return 0;
        });
        var ul = $( '#users' );
        $.each( items, function( i, li ){
            ul.append( li );
        });

    });

    socketio.on( "message_to_client", function ( data )
    {
        var messageText = data.message;
        messageText = messageText.split( "\n" ).join( "<br/>" );

        var _ultimaDataPostada = getDataAtual();

        if( ultimaDataPostada != _ultimaDataPostada )
            addMessageStatus( "HOJE" );

        ultimaDataPostada = _ultimaDataPostada;

        var who = 'you';
        if ( data.userid == user.userid ) {
            who = 'me';
            autoSroll = true;
        }

        // Mounts the message
        var li = null;
        if( ultimaMensagemDe == data.userid )
        {
            // If the last message sent chatting is not that person,
            // places the image
            li = $( 
                '<li class="' + who + ' no-name">' +
                    '<div class="image off"></div>' +
                    '<div class="message">'+
                    '<div>' + messageText + '</div>' +
                        '<time>'+getHoraAtual()+'</time>' +
                    '</div>' +
                    '<div class="clear"></div>' +
                '</li>' );
        }
        else{
            // Otherwise poses no image
            li = $( 
                '<li class=' + who + '>' +
                    '<div class="image">' +
                        '<img src=' + data.photo + ' />' +
                    '</div>' +
                    '<div class="message">'+
                        '<b class="no-text">' + data.fullname + '</b>' +
                        '<div>' + messageText + '</div>' +
                        '<time class="no-text">'+getHoraAtual()+'</time>' +
                    '</div>' +
                    '<div class="clear"></div>' +
                '</li>' );
        }

        // Guard who wrote
        ultimaMensagemDe = data.userid;

        // add to actual chat
        chats.append( li );
        // Scroll to booton
        scrollToBottom();
    });

    if( document.getElementById('form_select_group') )
    {
        $('#select_group').change(function(){
            $('#form_select_group').submit();
        });
    }

    // Click send message
    submit.click( sendMessage );

    // pressed enter
    message.keypress( function ( event )
    {
        if ( event.which == 13 && !event.shiftKey )
            sendMessage();
    });

    // keyup event
    message.keyup( function ( event )
    {
        if ( event.which == 13 && !event.shiftKey )
            message.html ( '' );

        if( message.text().trim().length > 0 )
        {
            if( message.html().indexOf( '<' ) != -1 )
                message.html( message.text() );

            submit.removeAttr( 'disabled' );
            submit.removeClass( 'disabled' );
            messagePlaceholder.hide();
            message.addClass ( 'with-text' );
            resizeBackgroundMessage();
        }
        else
            clearMessage();
    });

    function sendMessage() {
        if ( message.text().trim().length > 0 ) {
            var dataUser = {
                message : message.text()
            };
            socketio.emit( "message_to_server", dataUser );

            message.html ( '' );
            message.focus();
            clearMessage();
        }
    }
    function clearMessage()
    {
        submit.attr( 'disabled', 'disabled' ).addClass( 'disabled' );
        messagePlaceholder.show();
        message.removeClass ( 'with-text' );

        resizeBackgroundMessage();
    }


    function addMessageStatus( status ) {
        var li = $( 
            '<li class="message-status no-text">' +
                '<span>' + status + '</span>' +
            '</li>' );

        chats.append( li );
        scrollToBottom();
        ultimaMensagemDe = 0;
    }

    chats.scroll ( function() {
        //console.log ( '----------------' );
        //console.log ( chats.height() );
        //console.log ( chats.scrollTop() );
        //console.log ( chats[0].scrollHeight );

        var atualScrollHeight = chats.height() + chats.scrollTop();
        var maxScrollHeight = chats[0].scrollHeight;

        autoSroll = atualScrollHeight == maxScrollHeight;
        if( autoSroll )
            newmessage.hide( 800 );
    });

    newmessage.click ( function(){
        autoSroll = true;
        scrollToBottom();
    });

    function scrollToBottom() {
        if( autoSroll )
        {
            // chats.stop().animate( { scrollTop : chats[0].scrollHeight - chats.height() }, '1500', 'swing' );
            chats.scrollTop( chats[0].scrollHeight - chats.height() );
            newmessage.hide();
        }
        else
            newmessage.show();
    }

    function getDataAtual() {
        var now = new Date();
        return now.getDate() + "/" + now.getMonth() + "/" + now.getFullYear();
    }

    function getHoraAtual() {
        var now = new Date();
        return addZero( now.getHours() ) + ':' + addZero( now.getMinutes() );
    }
    function addZero( num )
    {
        if( num < 10 )
            return "0" + num;
        return "" + num;
    }


    function resizeBackgroundMessage()
    {
        if( htmlBody.width() < 500 )
            message.width( htmlBody.width() - 144 );
        else if( htmlBody.width() < 800 )
            message.width( htmlBody.width() - 156  );
        else
            message.width( 423 );

        messagePlaceholder.height( message.height()+1 );
        messagePlaceholder.width( message.width()+1 );
    }
    $( window ).resize ( function(){
        resizeBackgroundMessage();
        scrollToBottom();
    });

    function showError ( numError, data )
    {
        chatfooter.find( '#message-area' ).hide();
        chatfooter.find( '#wait-area' ).hide();
        chatfooter.find( '.status-area-error' ).hide();
        chatfooter.find( '#error-area' + numError ).show().find('span').html( data );
    }
});