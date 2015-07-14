/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

var websocket_status = false;
var websocket_interval = null;
var websocket_heap = [];
var ws = null;

var websocket_events = {
    open: function (event) {
        console.log('[WS] Connection established');
        websocket_status = true;
        if (null !== websocket_interval) {
            clearInterval(websocket_interval);
        }
        websocket_interval = null;

        websocket_heap.forEach(function (item) {
            var message;
            if (typeof(item) === 'string') {
                message = item;
            } else {
                message = JSON.stringify(item);
            }

            ws.send(message);
        });
        websocket_heap = [];

        ws.send('{"type":"info"}');
    },

    message: function (event) {
        console.log('[WS] Message received');

        try {
            var message = JSON.parse(event.data);
            if (('type' in message) === false) {
                return;
            }

            switch (message.type) {
                case 'new':
                    showNewBet(message.from, message.to);
                    addLostBet(message);
                    break;
                case 'paid':
                    showBetPaid(message.from, message.to);
                    betIsPaid(message.from, message.to);
                    break;
                case 'info':
                    buildParticipants(message.participants);
                    buildLostBets(message.bets);
                    break;
            }
        } catch (e) {
            console.error(e.message);
        }
    },

    close: function (event) {
        console.log('[WS] Connection closed');
        websocket_status = false;
        ws = null;
        if (null === websocket_interval) {
            websocket_interval = setInterval(tryWsConnection, 10000);
        }
    },

    error: function (event) {
        console.log('[WS] error received');

    }
};

function tryWsConnection()
{
    console.log('[WS] try connection');
    ws = new WebSocket(websocket_server);
    ws.onopen = websocket_events.open;
    ws.onmessage = websocket_events.message;
    ws.onclose = websocket_events.close;
    ws.onerror = websocket_events.error;
}

tryWsConnection();
