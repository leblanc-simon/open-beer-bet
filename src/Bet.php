<?php
/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

namespace OpenBeerBet;

use Monolog\Logger;
use OpenBeerBet\Bet\NewBet;
use OpenBeerBet\Bet\PaidBet;
use Predis\Client;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Bet implements MessageComponentInterface
{
    /**
     * @var \Predis\Client
     */
    private $predis;

    /**
     * @var \SplObjectStorage
     */
    private $clients;

    /**
     * @var string
     */
    private $participants_file;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Client $predis
     * @param string $participants_file
     * @param Logger $logger
     */
    public function __construct(Client $predis, $participants_file, Logger $logger = null)
    {
        $this->predis = $predis;
        $this->participants_file = $participants_file;
        $this->clients = new \SplObjectStorage();
        $this->logger = $logger;
    }

    public function onOpen(ConnectionInterface $connection)
    {
        $this->clients->attach($connection);
        if (null !== $this->logger) {
            $this->logger->addInfo(sprintf(
                'New connection : %s',
                $connection->resourceId
            ));
        }
    }

    public function onMessage(ConnectionInterface $from, $message)
    {
        $numRecv = count($this->clients) - 1;

        if (null !== $this->logger) {
            $this->logger->addInfo(sprintf(
                'Connection %d sending message "%s" to %d other connection%s',
                $from->resourceId, $message, $numRecv, $numRecv == 1 ? '' : 's'
            ));
        }

        try {
            $object_message = json_decode($message);
            if (property_exists($object_message, 'type') === false) {
                throw new \InvalidArgumentException('type must be define in message');
            }

            switch ($object_message->type) {
                case 'new':
                    (new NewBet($this->predis, $this->clients, $this->logger))
                        ->save($message)->dispatch($from);
                    break;
                case 'paid':
                    (new PaidBet($this->predis, $this->clients, $this->logger))
                        ->save($message)->dispatch($from);
                    break;
                case 'info':
                    (new Information($this->predis, $this->participants_file))
                        ->dispatch($from);
                    break;
            }
        } catch (\Exception $e) {
            if (null !== $this->logger) {
                $this->logger->addCritical(sprintf(
                    'An exception throw : %s',
                    $e->getMessage()
                ));
            }
        }
    }

    public function onClose(ConnectionInterface $connection)
    {
        $this->clients->detach($connection);

        if (null !== $this->logger) {
            $this->logger->addInfo(sprintf(
                'Connection %s has disconnected',
                $connection->resourceId
            ));
        }
    }

    public function onError(ConnectionInterface $connection, \Exception $e)
    {
        if (null !== $this->logger) {
            $this->logger->addInfo(sprintf(
                'An error has occurred: %s',
                $e->getMessage()
            ));
        }

        $connection->close();
    }
}
