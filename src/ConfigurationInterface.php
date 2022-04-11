<?php

declare(strict_types=1);

namespace Spacetab\Configuration;

/**
 * Interface ConfigurationInterface
 *
 * @package Spacetab\Configuration
 */
interface ConfigurationInterface
{
    /**
     * Get's a value from config by dot notation
     * E.g get('x.y', 'foo') => returns the value of $config['x']['y']
     * And if not exist, return 'foo'
     *
     * Supported dot-notation syntax with an asterisk.
     * You can read about it here: https://github.com/spacetab-io/obelix-php
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Gets all the tree config
     *
     * @return array
     */
    public function all(): array;
}
