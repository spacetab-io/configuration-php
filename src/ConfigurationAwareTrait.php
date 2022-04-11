<?php

declare(strict_types=1);

namespace Spacetab\Configuration;

/**
 * Basic Implementation of ConfigurationAwareInterface.
 */
trait ConfigurationAwareTrait
{
    /**
     * The Configuration instance.
     *
     * @var ConfigurationInterface
     */
    protected ConfigurationInterface $configuration;

    /**
     * Sets a configuration.
     *
     * @param ConfigurationInterface $configuration
     */
    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->configuration = $configuration;
    }
}

