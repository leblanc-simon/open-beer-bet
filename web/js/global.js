/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

// Image for Drag & Drop
var beer = new Image();
beer.src = './beer-72.png';

// Image for line of bet
var beer_bet = new Image();
beer_bet.src = './beer-48.png';
beer_bet.width = 48;
beer_bet.height = 48;

// Languages
var current_language, lang = {};
if (navigator.language) {
    current_language = navigator.language.substring(0, 2);
} else {
    current_language = default_language;
}

function buildLostBet(name, to)
{
    var element_beer = beer_bet.cloneNode(false);
    element_beer.draggable = false;
    var container = document.createElement('div');
    var element_name = document.createElement('span');
    var element_paid = document.createElement('a');

    element_name.textContent = name;
    element_paid.className = 'paid';
    container.className = 'lost-bet';

    container.setAttribute('data-from', name);
    container.setAttribute('data-to', to);

    element_paid.addEventListener('click', function (event) {
        event.target.parentNode.remove();

        var message = {
            from: name,
            to: to,
            type: 'paid'
        };
        if (null !== ws && true === websocket_status) {
            ws.send(JSON.stringify(message));
        } else {
            websocket_heap.push(message);
        }
    });

    container.appendChild(element_beer);
    container.appendChild(element_name);
    container.appendChild(element_paid);

    return container;
}

function addLostBet(bet)
{
    var bets_element = document.querySelector('#' + bet.to + ' .bets');
    bets_element.appendChild(buildLostBet(bet.from, bet.to));
}

function buildLostBets(bets)
{
    bets.forEach(function (bet) {
        addLostBet(bet)
    });
}

function buildParticipants(participants)
{
    var global_bets = document.getElementById('bets');
    var participant;

    // Remove old participants
    while (global_bets.firstChild) {
        global_bets.removeChild(global_bets.firstChild);
    }

    function showEffect(element_participant)
    {
        element_participant.className = 'participant';
    }

    for (iterator in participants) {
        participant = participants[iterator];

        var element_beer = beer_bet.cloneNode(false);
        var element_participant = document.createElement('div');
        var element_name = document.createElement('div');
        var element_bets = document.createElement('div');
        element_participant.id = participant;
        element_name.className = 'name';
        element_name.textContent = participant;
        element_beer.setAttribute('data-for', participant);
        element_name.appendChild(element_beer);
        element_bets.className = 'bets';

        element_participant.appendChild(element_name);
        element_participant.appendChild(element_bets);

        global_bets.appendChild(element_participant);

        setTimeout(showEffect, parseInt(iterator) * 200, element_participant);
    }

    initDnd();
}

function betIsPaid(from, to)
{
    var lost_bet = document.querySelector('[data-to="' + to + '"][data-from="' + from + '"]');
    lost_bet.remove();
}

if (!String.prototype.format) {
    String.prototype.format = function() {
        var args = arguments;
        return this.replace(/{(\d+)}/g, function(match, number) {
            return typeof args[number] != 'undefined'
                ? args[number]
                : match
                ;
        });
    };
}
