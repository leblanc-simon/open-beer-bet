var mobile = {
    init: function () {},
    touch: function (element) {
        element.addEventListener('touchend', function (event) {
            event.preventDefault();

            if (document.getElementById('mobile-choice')) {
                document.getElementById('mobile-choice').outerHTML = '';
                delete document.getElementById('mobile-choice');
            }

            var current_participant = element.parentNode.parentNode.getAttribute('id');

            // create select
            var participants = Array.from(document.getElementsByClassName('participant'));
            participants = participants
                .filter(function (participant) {
                    return participant.getAttribute('id') !== current_participant;
                })
                .map(function (participant) {
                    var option = document.createElement('option');
                    var participant_id = participant.getAttribute('id');
                    option.value = participant_id;
                    option.innerHTML = participant_id;
                    return option;
                })
            ;

            var select = document.createElement('select');
            select.style.visibility = "hidden";
            select.setAttribute('id', 'mobile-choice');
            var default_value = document.createElement('option');
            default_value.innerHTML = lang[current_language].option.default_value;
            select.appendChild(default_value);

            participants.forEach(function (option_participant) {
                select.appendChild(option_participant);
            });

            select.addEventListener('change', function (event) {
                var message = {
                    from: current_participant,
                    to: event.target.value,
                    type: 'new'
                };

                if (null !== ws && true === websocket_status) {
                    ws.send(JSON.stringify(message));
                } else {
                    websocket_heap.push(message);
                }

                document.getElementById(event.target.value).lastChild.appendChild(
                    buildLostBet(current_participant, event.target.value)
                );

                document.getElementById('mobile-choice').outerHTML = '';
                delete document.getElementById('mobile-choice');
            });

            document.body.appendChild(select);

            // Show select : if no timeout, it doesn't work... I don't know why...
            setTimeout(function () {
                openSelect(document.getElementById('mobile-choice'));
            }, 100);
        });
    }
};

if ('ontouchstart' in document.documentElement) {
    mobile.init = function () {
        var count, iterator;
        var touchers = document.querySelectorAll('.name img');
        for (count = touchers.length, iterator = 0; iterator < count; iterator++) {
            mobile.touch(touchers[iterator]);
        }
    };
}

function openSelect(element)
{
    if (document.createEvent) {
        //var event = document.createEvent('MouseEvents');
        //event.initMouseEvent('mousedown', true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
        var event = new MouseEvent('mousedown', {
            'view': window,
            'bubbles': true,
            'cancelable': true
        });
        element.dispatchEvent(event);
    } else if (element.fireEvent) {
        element.fireEvent('onmousedown');
    }
}

// Production steps of ECMA-262, Edition 6, 22.1.2.1
if (!Array.from) {
    Array.from = (function () {
        var toStr = Object.prototype.toString;
        var isCallable = function (fn) {
            return typeof fn === 'function' || toStr.call(fn) === '[object Function]';
        };
        var toInteger = function (value) {
            var number = Number(value);
            if (isNaN(number)) { return 0; }
            if (number === 0 || !isFinite(number)) { return number; }
            return (number > 0 ? 1 : -1) * Math.floor(Math.abs(number));
        };
        var maxSafeInteger = Math.pow(2, 53) - 1;
        var toLength = function (value) {
            var len = toInteger(value);
            return Math.min(Math.max(len, 0), maxSafeInteger);
        };

        // The length property of the from method is 1.
        return function from(arrayLike/*, mapFn, thisArg */) {
            // 1. Let C be the this value.
            var C = this;

            // 2. Let items be ToObject(arrayLike).
            var items = Object(arrayLike);

            // 3. ReturnIfAbrupt(items).
            if (arrayLike == null) {
                throw new TypeError('Array.from requires an array-like object - not null or undefined');
            }

            // 4. If mapfn is undefined, then let mapping be false.
            var mapFn = arguments.length > 1 ? arguments[1] : void undefined;
            var T;
            if (typeof mapFn !== 'undefined') {
                // 5. else
                // 5. a If IsCallable(mapfn) is false, throw a TypeError exception.
                if (!isCallable(mapFn)) {
                    throw new TypeError('Array.from: when provided, the second argument must be a function');
                }

                // 5. b. If thisArg was supplied, let T be thisArg; else let T be undefined.
                if (arguments.length > 2) {
                    T = arguments[2];
                }
            }

            // 10. Let lenValue be Get(items, "length").
            // 11. Let len be ToLength(lenValue).
            var len = toLength(items.length);

            // 13. If IsConstructor(C) is true, then
            // 13. a. Let A be the result of calling the [[Construct]] internal method
            // of C with an argument list containing the single item len.
            // 14. a. Else, Let A be ArrayCreate(len).
            var A = isCallable(C) ? Object(new C(len)) : new Array(len);

            // 16. Let k be 0.
            var k = 0;
            // 17. Repeat, while k < len… (also steps a - h)
            var kValue;
            while (k < len) {
                kValue = items[k];
                if (mapFn) {
                    A[k] = typeof T === 'undefined' ? mapFn(kValue, k) : mapFn.call(T, kValue, k);
                } else {
                    A[k] = kValue;
                }
                k += 1;
            }
            // 18. Let putStatus be Put(A, "length", len, true).
            A.length = len;
            // 20. Return A.
            return A;
        };
    }());
}