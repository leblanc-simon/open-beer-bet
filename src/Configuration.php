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
     * @var array<string, mixed>
     */
    private array $configuration = [];

    public function __construct(
        private readonly string $filename,
        /** @var array<string, mixed> */
        private readonly array $default = [],
    ) {
    }

    public function load(): static
    {
        if (is_file($this->filename) === false) {
            $this->configuration = $this->default;
            return $this;
        }

        try {
            $yaml = new Parser();
            $this->configuration = $yaml->parse(file_get_contents($this->filename));
        } catch (ParseException) {
            $this->configuration = $this->default;
        }

        return $this;
    }

    public function get(string $configuration)
    {
        $keys = explode('.', $configuration);

        $value = $this->getFromConfiguration($configuration, $keys, $this->configuration);

        return $value ?? $this->getFromConfiguration($configuration, $keys, $this->default);
    }

    private function getFromConfiguration(string $search, array $keys, array $current_configuration)
    {
        foreach ($keys as $key) {
            $is_last = strpos($search, $key) === (strrpos($search, '.') + 1);
            if (false === is_array($current_configuration) || false === array_key_exists($key, $current_configuration)) {
                return null;
            }
            if (true === $is_last) {
                return $current_configuration[$key];
            }

            $current_configuration = $current_configuration[$key];
        }

        return null;
    }
}
