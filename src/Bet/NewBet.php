<?php
/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

namespace OpenBeerBet\Bet;

use JsonException;
use Ratchet\ConnectionInterface;

class NewBet extends BetAbstract implements BetInterface
{
    /**
     * Save the state of bet.
     *
     * @param string $message the original message received from websocket client
     * @return $this
     * @throws JsonException
     */
    public function save(string $message): self
    {
        $this->extractBetFromMessage($message);
        $this->bet->date = date('Y-m-d H:i:s');
        $this->predis->rpush(self::REDIS_LIST, json_encode($this->bet));

        return $this;
    }

    /**
     * Dispatch the information of the websocket client.
     *
     * @param ConnectionInterface $from the sender
     * @return self
     * @throws JsonException
     */
    public function dispatch(ConnectionInterface $from): self
    {
        return $this->sendMessage($from, 'new');
    }
}
