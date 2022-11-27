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

class PaidBet extends BetAbstract implements BetInterface
{
    /**
     * Save the state of bet.
     *
     * @param string $message the original message received from websocket client
     * @return self
     * @throws JsonException
     */
    public function save(string $message): self
    {
        $this->extractBetFromMessage($message);

        $bets = $this->predis->lrange(self::REDIS_LIST, 0, -1);
        foreach ($bets as $bet) {
            $bet = json_decode($bet, false, 512, JSON_THROW_ON_ERROR);
            if ($bet->to === $this->bet->to && $bet->from === $this->bet->from) {
                $this->predis->lrem(self::REDIS_LIST, 1, json_encode($bet, JSON_THROW_ON_ERROR));
                break;
            }
        }

        return $this;
    }

    /**
     * Dispatch the information of the websocket client
     *
     * @param ConnectionInterface $from the sender
     * @return self
     * @throws JsonException
     */
    public function dispatch(ConnectionInterface $from): self
    {
        return $this->sendMessage($from, 'paid');
    }
}
