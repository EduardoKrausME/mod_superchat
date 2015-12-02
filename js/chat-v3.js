// only execute on start jquery
$(function() {
    var chats = $("#chats"),
        submit = $('#submit'),
        chatfooter = $('#chatfooter'),
        messageBackground = $('#message-background'),
        messagePlaceholder = $('#message-placeholder'),
        message = $('#message'),
        userid = $('#userid'),
        fullname = $('#fullname'),
        photo = $('#photo'),
        ultimaMensagemDe = 0,
        ultimaDataPostada = getDataAtual();

    // Hide the footer to connect
    chatfooter.hide();

    // Connects to the server node js
    var socketio = io.connect($('#server_host').val()+":"+$('#server_port').val());

    // When connecting make:
    socketio.on('connect', function()
    {
        socketio.emit( "login",
            {
                userid   : userid.val(),
                room     : $('#server_room').val(),
                photo    : photo.val(),
                fullname : fullname.val()
            }
        );

        // Show message área
        chatfooter.show();
        // Clear message area
        message.html('').focus();
        // resize background área
        resizeBackgroundMessage();
    });

    // When you receive a new message do:
    socketio.on("message_to_client", function (data)
    {
        var messageText = data.message;
        messageText = messageText.split("\n").join("<br/>");

        var _ultimaDataPostada = getDataAtual();

        if( ultimaDataPostada != _ultimaDataPostada )
            addMessageStatus("HOJE");

        ultimaDataPostada = _ultimaDataPostada;

        var who = 'you';
        if (data.userid == userid.val())
            who = 'me';

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
                        '<i>'+getHoraAtual()+'</i>' +
                    '</div>' +
                    '<div class="clear"></div>' +
                '</li>');
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
                        '<i class="no-text">'+getHoraAtual()+'</i>' +
                    '</div>' +
                    '<div class="clear"></div>' +
                '</li>');
        }

        // Guard who wrote
        ultimaMensagemDe = data.userid;

        // add to actual chat
        chats.append(li);
        // Scroll to booton
        scrollToBottom();
    });

    // if the user is the ONline do:
    socketio.on("online", function (data) {
        $('#student_' + data.userid).addClass('online');

        if (data.userid != userid.val())
            addMessageStatus(data.fullname + " ENTROU");
    });
    // if the user is the OFFline do:
    socketio.on("offline", function (data) {
        $('#student_' + data.userid).removeClass('online');
        if (data.userid != userid.val())
            addMessageStatus(data.fullname + " SAIU");
    });

    // Click send message
    submit.click(sendMessage);
    // pressed enter
    message.keypress(function (event) {
        if ( event.which == 13 && !event.shiftKey )
            sendMessage();
    });
    // keyup event
    message.keyup(function (event)
    {
        if( message.text().trim().length > 0 )
        {
            if( message.html().indexOf('<') != -1 )
                message.html(message.text());

            submit.removeAttr('disabled');
            submit.removeClass('disabled');
            messagePlaceholder.hide();
        }
        else
        {
            submit.attr('disabled', 'disabled');
            submit.addClass('disabled');
            messagePlaceholder.show();
        }
        resizeBackgroundMessage();
    });

    function sendMessage() {
        event.preventDefault();

        if (message.text().trim().length > 0) {
            var dataUser = {
                userid: userid.val(),
                message: message.text(),
                photo: photo.val(),
                fullname: fullname.val()
            };

            socketio.emit("message_to_server", dataUser);

            message.html('').focus();
            submit.attr('disabled', 'disabled');
            submit.addClass('disabled');
            resizeBackgroundMessage();
        }
    }


    function addMessageStatus(status) {
        var li = $(
            '<li class="message-status no-text">' +
                '<span>' + status + '</span>' +
            '</li>');

        chats.append(li);
        scrollToBottom();
        ultimaMensagemDe = 0;
    }

    function scrollToBottom() {
        chats.scrollTop(100000000000000);
    }

    function getDataAtual() {
        var now = new Date();
        return now.getDate() + "/" + now.getMonth() + "/" + now.getFullYear();
    }

    function getHoraAtual() {
        var now = new Date();
        return addZero(now.getHours()) + ':' + addZero(now.getMinutes());
    }
    function addZero(num)
    {
        if( num < 10 )
            return "0" + num;
        return "" + num;
    }


    function resizeBackgroundMessage() {
        messageBackground.height( message.height()+1 );
        messageBackground.width( message.width()+1 );
    }
});