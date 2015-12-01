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

    chatfooter.hide();

    var socketio = io.connect($('#server_host').val()+":"+$('#server_port').val());

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

        chatfooter.show();
        message.html('').focus();
        resizeBackgroundMessage();
    });

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

        var li = null;
        if( ultimaMensagemDe == data.userid )
        {
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

        ultimaMensagemDe = data.userid;

        chats.append(li);
        scrollToBottom();
    });

    socketio.emit( "server_add_user",
        {
            fullname: fullname.val(),
            userid   : userid.val()
        }
    );

    socketio.on("client_add_user", function (data) {
        if (data.userid != userid.val())
            addMessageStatus(data.fullname + " entrou");
    });

    socketio.on("online", function (data) {
        $('#student_' + data.userid).addClass('online')
    });
    socketio.on("offline", function (data) {
        $('#student_' + data.userid).removeClass('online')
    });

    submit.click(sendMessage);
    message.keypress(function (event) {
        if ( event.which == 13 && !event.shiftKey )
            sendMessage();
    });
    message.keyup(function (event){
        if( message.text().length > 0 )
        {
            if( message.html().indexOf('<') != -1 )
            {
                console.log('Clear text');
                message.html(message.text());
            }

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

        if (message.text().length > 0) {
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