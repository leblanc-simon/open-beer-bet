<?php
/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

namespace OpenBeerBet\Bet;

use Ratchet\ConnectionInterface;

class PaidBet extends BetAbstract implements BetInterface
{
    /**
     * Save the state of bet
     * @param string $message the original message received from websocket client
     * @return $this
     */
    public function save($message)
    {
        $this->extractBetFromMessage($message);

        $bets = $this->predis->lrange(self::REDIS_LIST, 0, -1);
        foreach ($bets as $bet) {
            $bet = json_decode($bet);
            if ($bet->to === $this->bet->to && $bet->from === $this->bet->from) {
                $this->predis->lrem(self::REDIS_LIST, 1, json_encode($bet));
                break;
            }
        }

        return $this;
    }

    /**
     * Dispatch the information of the websocket client
     * @param ConnectionInterface $from the sender
     * @return $this
     */
    public function dispatch(ConnectionInterface $from)
    {
        return $this->sendMessage($from, 'paid');
    }
}
