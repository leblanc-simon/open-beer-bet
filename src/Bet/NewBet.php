<?php
/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

namespace OpenBeerBet\Bet;

use Ratchet\ConnectionInterface;

class NewBet extends BetAbstract implements BetInterface
{
    /**
     * Save the state of bet
     * @param string $message the original message received from websocket client
     * @return $this
     */
    public function save($message)
    {
        $this->extractBetFromMessage($message);
        $this->bet->date = date('Y-m-d H:i:s');
        $this->predis->rpush(self::REDIS_LIST, json_encode($this->bet));

        return $this;
    }

    /**
     * Dispatch the information of the websocket client
     * @param ConnectionInterface $from the sender
     * @return $this
     */
    public function dispatch(ConnectionInterface $from)
    {
        return $this->sendMessage($from, 'new');
    }
}
