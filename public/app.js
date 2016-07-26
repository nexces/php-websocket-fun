/**
 * Created by nexce_000 on 21.07.2016.
 */

var socket = new WebSocket('ws://10.111.246.136:8080/ws');


socket.addEventListener('open', /** @type Event e */ function (e) {
    console.log('Socket::open() => %O', e);
});
socket.addEventListener('message', /** @type MessageEvent e */ function (e) {
    console.log('Socket::message() => %O', e);
    var data = JSON.parse(e.data);
    console.log('Socket::message() => %O', data);

    if (data.type == "auth") {
        if (data.result) {
            document.body.classList.add('authenticated');
            document.getElementById('chatMessage').focus();
        } else {
            document.body.classList.remove('authenticated');
        }
    } else if (data.type == "clients") {
        var $users = document.getElementById('chat').querySelector('.users');
        $users.innerHTML = '';
        for (var i = 0; i < data.clients.length; i++) {
            $users.innerHTML += '<div>' + data.clients[i] + '</div>';
        }
    } else if (data.type == "message") {
        var $messages = document.getElementById('chat').querySelector('.chat');
        $messages.innerHTML += '<div>' + data.date + ' / ' + data.from + ': ' + data.text + '</div>';
    }
});
socket.addEventListener('close', /** @type Event e */ function (e) {
    console.log('Socket::close() => %O', e);
});


document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('loginUser').focus();
    document.getElementById('login').addEventListener('submit', /** @type Event e */ function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        e.stopPropagation();

        socket.send(
            JSON.stringify(
                {
                    type: "auth",
                    user: document.getElementById('loginUser').value,
                    password: document.getElementById('loginPassword').value
                }
            )
        );
    });

    document.getElementById('chat').addEventListener('submit', /** @type Event e */ function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        e.stopPropagation();

        socket.send(
            JSON.stringify(
                {
                    type: "chat",
                    text: document.getElementById('chatMessage').value
                }
            )
        );

        document.getElementById('chatMessage').value = '';
    });
});
