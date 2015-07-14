/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

var dnd = {
    current_element: null,
    current_parent: null,

    dragEvent: function (element) {
        element.draggable = true;
        var dnd = this;

        element.addEventListener('dragstart', function(event) {
            dnd.current_element = event.target; // On sauvegarde l'élément en cours de déplacement
            dnd.current_parent = event.target.parentNode.parentNode;

            event.dataTransfer.setDragImage(beer, 36, 36);
        }, false);

        element.addEventListener('drop', function(e) {
            e.stopPropagation(); // On stoppe la propagation de l'événement pour empêcher la zone de drop d'agir
        }, false);
    },

    dropEvent: function (element) {
        var dnd = this;

        element.addEventListener('dragover', function(event) {
            event.preventDefault();

            if (dnd.isParent(event) === true) {
                return;
            }

            var target = event.target;

            while (target.className.indexOf('participant') == -1) { // Cette boucle permet de remonter jusqu'à la zone de drop parente
                target = target.parentNode;
            }

            target.style.borderStyle = 'dashed';
        }, false);

        element.addEventListener('dragleave', function (event) {
            if (dnd.isParent(event) === true) {
                return;
            }

            var target = event.target;

            while (target.className.indexOf('participant') == -1) { // Cette boucle permet de remonter jusqu'à la zone de drop parente
                target = target.parentNode;
            }

            target.style.borderStyle = 'solid';
        });

        element.addEventListener('drop', function (event) {
            if (event.preventDefault) { event.preventDefault(); }
            if (event.stopPropagation) { event.stopPropagation(); }

            if (dnd.isParent(event) === true) {
                return false;
            }

            var target = event.target;

            while (target.className.indexOf('bets') == -1) {
                target = target.parentNode;
            }

            target.appendChild(dnd.dropBeer(target.previousElementSibling.textContent));
            target.parentNode.style.borderStyle = 'solid';
            return false;
        });
    },

    dropBeer: function (to) {
        var dnd = this;

        var is_for = dnd.current_element.getAttribute('data-for');
        var name = document.querySelector('#' + is_for + ' .name').textContent;

        var message = {
            from: name,
            to: to,
            type: 'new'
        };
        if (null !== ws && true === websocket_status) {
            ws.send(JSON.stringify(message));
        } else {
            websocket_heap.push(message);
        }

        return buildLostBet(name, to);
    },

    isParent: function (event) {
        var dnd = this;

        var target = event.target;

        while (target.className.indexOf('participant') == -1) {
            target = target.parentNode;
        }

        return target === dnd.current_parent;
    }
};

function initDnd()
{
    var count, iterator;
    var draggers = document.querySelectorAll('.name img');
    for (count = draggers.length, iterator = 0; iterator < count; iterator++) {
        dnd.dragEvent(draggers[iterator]);
    }

    var droppers = document.querySelectorAll('.bets');
    for (count = droppers.length, iterator = 0; iterator < count; iterator++) {
        dnd.dropEvent(droppers[iterator]);
    }
}
