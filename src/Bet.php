<?php
/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

namespace OpenBeerBet;

use Exception;
use InvalidArgumentException;
use Monolog\Logger;
use OpenBeerBet\Bet\NewBet;
use OpenBeerBet\Bet\PaidBet;
use Predis\Client;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;
use Throwable;

class Bet implements MessageComponentInterface
{
    /**
     * @var SplObjectStorage<ConnectionInterface>
     */
    private SplObjectStorage $clients;

    public function __construct(
        private readonly Client  $predis,
        private readonly string  $participantsFile,
        private readonly ?Logger $logger = null
    ) {
        $this->clients = new SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        $this->logger?->info(sprintf(
            'New connection : %s',
            $conn->resourceId
        ));
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $numRecv = count($this->clients) - 1;

        $this->logger?->info(sprintf(
            'Connection %d sending message "%s" to %d other connection%s',
            $from->resourceId, $msg, $numRecv, $numRecv === 1 ? '' : 's'
        ));

        try {
            $object_message = json_decode($msg, false, 512, JSON_THROW_ON_ERROR);
            if (property_exists($object_message, 'type') === false) {
                throw new InvalidArgumentException('type must be define in message');
            }

            switch ($object_message->type) {
                case 'new':
                    (new NewBet($this->predis, $this->clients, $this->logger))
                        ->save($msg)->dispatch($from);
                    break;
                case 'paid':
                    (new PaidBet($this->predis, $this->clients, $this->logger))
                        ->save($msg)->dispatch($from);
                    break;
                case 'info':
                    (new Information($this->predis, $this->participantsFile))
                        ->dispatch($from);
                    break;
            }
        } catch (Throwable $e) {
            $this->logger?->critical(sprintf(
                'An exception throw : %s',
                $e->getMessage()
            ));
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);

        $this->logger?->info(sprintf(
            'Connection %s has disconnected',
            $conn->resourceId
        ));
    }

    public function onError(ConnectionInterface $conn, Exception $e): void
    {
        $this->logger?->info(sprintf(
            'An error has occurred: %s',
            $e->getMessage()
        ));

        $conn->close();
    }
}
