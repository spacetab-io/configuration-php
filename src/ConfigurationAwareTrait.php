<?php declare(strict_types=1);

namespace Spacetab\Configuration;

/**
 * Basic Implementation of ConfigurationAwareInterface.
 */
trait ConfigurationAwareTrait
{
    /**
     * The Configuration instance.
     *
     * @var \Spacetab\Configuration\ConfigurationInterface
     */
    protected $configuration;

    /**
     * Sets a configuration.
     *
     * @param \Spacetab\Configuration\ConfigurationInterface $configuration
     */
    public function setConfiguration(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }
}

