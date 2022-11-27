<?php
/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use OpenBeerBet\Bet;

require dirname(__DIR__) . '/vendor/autoload.php';

$configuration_file = dirname(__DIR__).'/config/config.yml';
$participants_file = dirname(__DIR__).'/config/participants.yml';

$configuration = new \OpenBeerBet\Configuration(
    $configuration_file,
    [
        'server' => [
            'port' => 8080,
            'log' => false,
        ],
        'redis' => [
            'connections' => null,
            'options' => null,
        ],
    ]
);
$configuration->load();

$predis = new \Predis\Client($configuration->get('redis.connections'), $configuration->get('redis.options'));

$logger = null;
if ($configuration->get('server.log') !== false) {
    $log_filename = $configuration->get('server.log.path');
    $log_level = $configuration->get('server.log.level');
    if (null !== $log_filename && null !== $log_level) {
        $logger = new Logger('openbeerbet');
        $logger->pushHandler(new RotatingFileHandler(
            $log_filename,
            20,
            constant('\\Monolog\\Logger::'.strtoupper($log_level))
        ));
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Bet($predis, dirname(__DIR__).'/config/participants.yml', $logger)
        )
    ),
    $configuration->get('server.port')
);

$server->run();
