<?php

declare(strict_types=1);

namespace Spacetab\Configuration;

/**
 * Describes a configuration-aware instance.
 */
interface ConfigurationAwareInterface
{
    /**
     * Sets a configuration instance on the object.
     *
     * @param \Spacetab\Configuration\ConfigurationInterface $configuration
     *
     * @return void
     */
    public function setConfiguration(ConfigurationInterface $configuration): void;
}

