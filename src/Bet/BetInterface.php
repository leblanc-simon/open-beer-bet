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
use SplObjectStorage;

interface BetInterface
{
    /**
     * @param Client $predis
     * @param SplObjectStorage<ConnectionInterface> $clients
     * @param Logger|null $logger
     */
    public function __construct(Client $predis, SplObjectStorage $clients, ?Logger $logger = null);

    /**
     * Save the state of bet.
     *
     * @param string $message the original message received from websocket client
     * @return $this
     */
    public function save(string $message): self;

    /**
     * Dispatch the information of the websocket client.
     *
     * @param ConnectionInterface $from the sender
     * @return $this
     */
    public function dispatch(ConnectionInterface $from): self;
}
