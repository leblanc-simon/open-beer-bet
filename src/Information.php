<?php
/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

namespace OpenBeerBet;

use JsonException;
use Monolog\Logger;
use OpenBeerBet\Bet\BetAbstract;
use Predis\Client;
use Ratchet\ConnectionInterface;
use StdClass;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class Information
{
    private array $participants = [];

    public function __construct(
        private readonly Client  $predis,
        private readonly string  $participantsFile,
        private readonly ?Logger $logger = null
    ) {
    }

    /**
     * @param ConnectionInterface $from
     * @throws JsonException
     */
    public function dispatch(ConnectionInterface $from): void
    {
        $bets = $this->predis->lrange(BetAbstract::REDIS_LIST, 0, -1);
        $list = [];

        foreach ($bets as $bet) {
            $list[] = json_decode($bet, false, 512, JSON_THROW_ON_ERROR);
        }

        $response = new StdClass();
        $response->type = 'info';
        $response->bets = $list;
        $response->participants = $this->getParticipants();

        $this->logger?->debug(sprintf(
            'Sent information : %s',
            json_encode($response, JSON_THROW_ON_ERROR)
        ));

        $from->send(json_encode($response, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array
     */
    private function getParticipants(): array
    {
        if (count($this->participants) !== 0) {
            return $this->participants;
        }

        if (is_file($this->participantsFile) === false) {
            return [];
        }

        $yaml = new Parser();
        try {
            $datas = $yaml->parse(file_get_contents($this->participantsFile));
            if (isset($datas['participants']) === true && is_array($datas['participants']) === true) {
                $this->participants = $datas['participants'];
                asort($this->participants); // Yes it's an issue (must be sort), but currently I don't accept the result for personal reasons :)
                return $this->participants;
            }
        } catch (ParseException $e) {
            $this->logger?->critical(sprintf(
                'Error while parsing participants.yml : %s',
                $e->getMessage()
            ));
        }

        return [];
    }
}
