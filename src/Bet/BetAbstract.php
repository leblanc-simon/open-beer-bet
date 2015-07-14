<?php
/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

namespace OpenBeerBet\Bet;

use Monolog\Logger;
use Predis\Client;
use Ratchet\ConnectionInterface;

abstract class BetAbstract
{
    const REDIS_LIST = 'OpenBeerBet-bets';

    /**
     * @var \Predis\Client
     */
    protected $predis;

    /**
     * @var \SplObjectStorage
     */
    protected $clients;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var \StdClass
     */
    protected $bet;

    /**
     * @param Client $predis Redis client - where the bets are saved
     * @param \SplObjectStorage $clients - Websocket clients
     * @param Logger $logger
     */
    public function __construct(Client $predis, \SplObjectStorage $clients, Logger $logger = null)
    {
        $this->predis = $predis;
        $this->clients = $clients;
        $this->logger = $logger;
        $this->bet = new \StdClass();
    }

    protected function sendMessage(ConnectionInterface $from, $type)
    {
        $this->bet->type = $type;
        $message = json_encode($this->bet);

        if (null !== $this->logger) {
            $this->logger->addDebug(sprintf(
                'Send message into clients : %s',
                $message
            ));
        }

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($message);
            }
        }

        return $this;
    }

    protected function extractBetFromMessage($message)
    {
        if (null !== $this->logger) {
            $this->logger->addDebug(sprintf(
                'Receive message from client : %s',
                $message
            ));
        }

        $bet = json_decode($message);
        if (null === $bet) {
            throw new \InvalidArgumentException('message cannot be decode');
        }

        if (property_exists($bet, 'from') === false) {
            throw new \InvalidArgumentException('message must have from');
        }

        if (property_exists($bet, 'to') === false) {
            throw new \InvalidArgumentException('message must have to');
        }

        $this->bet->from = trim($bet->from);
        $this->bet->to = trim($bet->to);
    }
}
