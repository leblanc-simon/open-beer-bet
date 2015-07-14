<?php
/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

namespace OpenBeerBet;

use Monolog\Logger;
use OpenBeerBet\Bet\BetAbstract;
use Predis\Client;
use Ratchet\ConnectionInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class Information
{
    /**
     * @var \Predis\Client
     */
    private $predis;

    /**
     * @var string
     */
    private $participants_file;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var array
     */
    private $participants;

    /**
     * @param Client $predis
     * @param string $participants_file
     * @param Logger $logger
     */
    public function __construct(Client $predis, $participants_file, Logger $logger = null)
    {
        $this->predis = $predis;
        $this->participants_file = $participants_file;
        $this->logger = $logger;
    }

    /**
     * @param ConnectionInterface $from
     */
    public function dispatch(ConnectionInterface $from)
    {
        $bets = $this->predis->lrange(BetAbstract::REDIS_LIST, 0, -1);
        $list = [];

        foreach ($bets as $bet) {
            $list[] = json_decode($bet);
        }

        $response = new \StdClass();
        $response->type = 'info';
        $response->bets = $list;
        $response->participants = $this->getParticipants();

        if (null !== $this->logger) {
            $this->logger->addDebug(sprintf(
                'Sent information : %s',
                json_encode($response)
            ));
        }

        $from->send(json_encode($response));
    }

    /**
     * @return array
     */
    private function getParticipants()
    {
        if ($this->participants !== null) {
            return $this->participants;
        }

        if (is_file($this->participants_file) === false) {
            return [];
        }

        $yaml = new Parser();
        try {
            $datas = $yaml->parse(file_get_contents($this->participants_file));
            if (isset($datas['participants']) === true && is_array($datas['participants']) === true) {
                $this->participants = $datas['participants'];
                asort($this->participants);
                return $this->participants;
            }
        } catch (ParseException $e) {
            if (null !== $this->logger) {
                $this->logger->addCritical(sprintf(
                    'Error while parsing participants.yml : %s',
                    $e->getMessage()
                ));
            }
        }

        return [];
    }
}
