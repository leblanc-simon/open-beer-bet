<?php
/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

namespace OpenBeerBet\Bet;

use InvalidArgumentException;
use JsonException;
use Monolog\Logger;
use Predis\Client;
use Ratchet\ConnectionInterface;
use SplObjectStorage;

abstract class BetAbstract
{
    public const REDIS_LIST = 'OpenBeerBet-bets';

    /**
     * @var \StdClass
     */
    protected \StdClass $bet;

    public function __construct(
        protected readonly Client $predis,
        /** @var SplObjectStorage<ConnectionInterface> */
        protected readonly SplObjectStorage $clients,
        protected readonly ?Logger $logger = null
    ) {
        $this->bet = new \StdClass();
    }

    /**
     * @throws JsonException
     */
    protected function sendMessage(ConnectionInterface $from, $type): static
    {
        $this->bet->type = $type;
        $message = json_encode($this->bet, JSON_THROW_ON_ERROR);

        $this->logger?->debug(sprintf(
            'Send message into clients : %s',
            $message
        ));

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($message);
            }
        }

        return $this;
    }

    /**
     * @throws JsonException
     */
    protected function extractBetFromMessage($message): void
    {
        $this->logger?->debug(sprintf(
            'Receive message from client : %s',
            $message
        ));

        $bet = json_decode($message, false, 512, JSON_THROW_ON_ERROR);
        if (null === $bet) {
            throw new InvalidArgumentException('message cannot be decode');
        }

        if (property_exists($bet, 'from') === false) {
            throw new InvalidArgumentException('message must have from');
        }

        if (property_exists($bet, 'to') === false) {
            throw new InvalidArgumentException('message must have to');
        }

        $this->bet->from = trim($bet->from);
        $this->bet->to = trim($bet->to);
    }
}
