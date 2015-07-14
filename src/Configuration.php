<?php
/**
 * Copyright © 2015 Simon Leblanc <contact@leblanc-simon.eu>
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 */

namespace OpenBeerBet;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class Configuration
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var array
     */
    private $default;

    /**
     * @var array
     */
    private $configuration;

    public function __construct($filename, array $default = [])
    {
        $this->filename = $filename;
        $this->default = $default;
    }

    public function load()
    {
        if (is_file($this->filename) === false) {
            $this->configuration = $this->default;
            return $this;
        }

        try {
            $yaml = new Parser();
            $this->configuration = $yaml->parse(file_get_contents($this->filename));
        } catch (ParseException $e) {
            $this->configuration = $this->default;
        }

        return $this;
    }

    public function get($configuration)
    {
        $keys = explode('.', $configuration);

        $value = $this->getFromConfiguration($configuration, $keys, $this->configuration);
        if (null !== $value) {
            return $value;
        }

        return $this->getFromConfiguration($configuration, $keys, $this->default);
    }

    private function getFromConfiguration($search, $keys, $current_configuration)
    {
        foreach ($keys as $key) {
            $is_last = strpos($search, $key) === (strrpos($search, '.') + 1);
            if (is_array($current_configuration) === true && array_key_exists($key, $current_configuration) === true) {
                if (true === $is_last) {
                    return $current_configuration[$key];
                }

                $current_configuration = $current_configuration[$key];
            } else {
                return null;
            }
        }

        return null;
    }
}
