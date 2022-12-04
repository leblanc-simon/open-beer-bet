<?php
/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

use OpenBeerBet\Configuration;
use Predis\Client;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use OpenBeerBet\Bet;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->load(dirname(__DIR__).'/.env');

if (false === str_starts_with($_ENV['CONFIGURATION_FILENAME'] ?? '', '/')) {
    $configurationFile  = dirname(__DIR__).'/'. $_ENV['CONFIGURATION_FILENAME'];
} else {
    $configurationFile  = $_ENV['CONFIGURATION_FILENAME'];
}

if (false === str_starts_with($_ENV['PARTICIPANTS_FILENAME'] ?? '', '/')) {
    $participantsFile  = dirname(__DIR__).'/'. $_ENV['PARTICIPANTS_FILENAME'];
} else {
    $participantsFile  = $_ENV['PARTICIPANTS_FILENAME'];
}

$configuration = new Configuration(
    $configurationFile,
    [
        'server' => [
            'port' => 8080,
            'address' => '127.0.0.1',
            'log' => false,
        ],
        'redis' => [
            'connections' => null,
            'options' => null,
        ],
    ]
);
$configuration->load();

$predis = new Client($configuration->get('redis.connections'), $configuration->get('redis.options'));

$logger = null;
if ($configuration->get('server.log') !== false) {
    $logFilename = $configuration->get('server.log.path');
    $logLevel = $configuration->get('server.log.level');
    if (null !== $logFilename && null !== $logLevel) {
        $logger = new Logger('openbeerbet');
        $logger->pushHandler(new RotatingFileHandler(
            $logFilename,
            20,
            constant('\\Monolog\\Logger::'.strtoupper($logLevel))
        ));
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Bet($predis, $participantsFile, $logger)
        )
    ),
    $configuration->get('server.port'),
    $configuration->get('server.address'),
);

$server->run();
