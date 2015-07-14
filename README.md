OpenBeerBet
===========

OpenBeerBet is a sample interface to manage beer bet !

It use Websockets, Web Notifications and Redis.

Installation
============

OpenBeerBet require a [Redis Server](http://redis.io/).

```bash
git clone https://github.com/leblanc-simon/open-beer-bet.git
cd open-beer-bet
composer install
cp config/participants.yml.dist config/participants.yml
cp web/js/config.js.dist web/js/config.js

# only if you want customize websockets port, log or redis configuration
cp config/config.yml.dist config/config.yml

php bin/server.php
```

Edit ```web/js/config.js``` and ```config/participants.yml``` with your own values.

Your webserver must have document_root into the ```web``` directory.

Credits
=======

* Ratchet : license MIT (http://socketo.me/)
* Predis : license MIT (https://github.com/nrk/predis)
* Symfony : license MIT (https://symfony.com/)
* Monolog : license MIT (https://github.com/Seldaek/monolog)

License
=======

Licence WTFPL : http://sam.zoy.org/wtfpl/

Author
======

Simon Leblanc <contact@leblanc-simon.eu>
