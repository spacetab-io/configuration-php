<?php

declare(strict_types=1);

namespace Spacetab\Configuration;

use ArrayAccess;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Spacetab\Configuration\Exception\ConfigurationException;
use Spacetab\Obelix;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Configuration
 *
 * @implements ArrayAccess<array-key, mixed>
 * @package Spacetab\Configuration
 */
final class Configuration implements ConfigurationInterface, ArrayAccess, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Possible path's of configuration.
     *
     * @var array<string>
     */
    private static array $possibleLocations = [
        '/app/configuration',
        '/configuration',
        './configuration',
        __DIR__ . '/../configuration',
    ];

    /**
     * Name of CONFIG_PATH variable.
     */
    private const CONFIG_PATH = 'CONFIG_PATH';

    /**
     * Name of stage ENV variable.
     */
    private const STAGE = 'STAGE';

    /**
     * Default configuration stage.
     */
    private const DEFAULT_STAGE = 'defaults';

    /**
     * Default config location.
     */
    private const DEFAULT_CONFIG_PATH = '/app/configuration';

    /**
     * Config tree goes here.
     */
    private Obelix\Dot $config;

    private string $path;
    private string $stage;

    /**
     * Configuration constructor.
     *
     * @param null|string $path
     * @param null|string $stage
     */
    public function __construct(?string $path = null, ?string $stage = null)
    {
        $envPath  = self::getEnvVariable(self::CONFIG_PATH, self::DEFAULT_CONFIG_PATH);
        $envStage = self::getEnvVariable(self::STAGE, self::DEFAULT_STAGE);

        null === $path ? $this->setPath($envPath) : $this->setPath($path);
        null === $stage ? $this->setStage($envStage) : $this->setStage($stage);

        // Set up default black hole logger.
        // If u want to see logs and see how load process working,
        // change it from outside to your default logger object in you application
        $this->setLogger(new NullLogger());
    }

    /**
     * Automatically find configuration in possible paths.
     * Specially for lazy-based programmers like me.
     *
     * @param string|null $stage
     * @throws ConfigurationException
     *
     * @return Configuration
     */
    public static function auto(?string $stage = null): Configuration
    {
        return new Configuration(self::findDirectories(), $stage);
    }

    /**
     * Gets a value from config by dot notation
     * E.g. get('x.y', 'foo') => returns the value of $config['x']['y']
     * And if not exist, return 'foo'.
     *
     * Supported dot-notation syntax with an asterisk.
     * You can read about it here: https://github.com/spacetab-io/obelix-php
     *
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config->get($key, $default)->getValue();
    }

    /**
     * Gets all the tree config.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->config->toArray();
    }

    /**
     * Set the configuration path.
     *
     * @param string $path
     *
     * @return Configuration
     */
    public function setPath(string $path): Configuration
    {
        $this->path = realpath($path) ?: $path;

        return $this;
    }

    /**
     * Get the configuration path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set stage for reading configuration.
     *
     * @param string $stage
     *
     * @return Configuration
     */
    public function setStage(string $stage): Configuration
    {
        $this->stage = $stage;

        return $this;
    }

    /**
     * @return string
     */
    public function getStage(): string
    {
        return $this->stage;
    }

    /**
     * Whether an offset exists
     *
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     *
     * @return boolean true on success or false on failure.
     */
    public function offsetExists(mixed $offset): bool
    {
        return ! empty($this->get($offset));
    }

    /**
     * Offset to retrieve
     *
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @since 5.0.0
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     *
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @since 5.0.0
     * @throws ConfigurationException
     *
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw ConfigurationException::operationNotAllowed();
    }

    /**
     * Offset to unset
     *
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @since 5.0.0
     * @throws ConfigurationException
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        throw ConfigurationException::operationNotAllowed();
    }

    /**
     * Initialize all the magic down here
     *
     * @throws ConfigurationException
     */
    public function load(): Configuration
    {
        $this->logger->info(self::CONFIG_PATH . ' = ' . $this->getPath());
        $this->logger->info(self::STAGE . ' = ' . $this->getStage());

        $second = $this->getStage() !== self::DEFAULT_STAGE
            ? $this->parseConfiguration($this->getStage())
            : [];

        $array = $this->arrayMergeRecursive(
            $this->parseConfiguration(),
            $second
        );

        $this->config = new Obelix\Dot($array);

        $this->logger->info('Configuration loaded.');

        return $this;
    }

    /**
     * For debug only.
     *
     * @param int $inline
     * @param int $indent
     * @return string
     */
    public function dump(int $inline = 10, int $indent = 2): string
    {
        return PHP_EOL . Yaml::dump($this->all(), $inline, $indent);
    }

    /**
     * Parses configuration and makes a tree of it.
     *
     * @param string $stage
     * @throws ConfigurationException
     *
     * @return array
     */
    private function parseConfiguration(string $stage = self::DEFAULT_STAGE): array
    {
        $pattern = $this->getPath() . '/' . $stage . '/*.yaml';
        $files   = glob($pattern, GLOB_NOSORT | GLOB_ERR);

        if ($files === false || count($files) < 1) {
            throw ConfigurationException::filesNotFound($pattern, $this->path, $this->stage);
        }

        $this->logger->debug('Following config files found:', $files);

        $config = [];
        foreach ($files as $filename) {
            $content   = Yaml::parseFile($filename);

            if (empty($content)) {
                $this->logger->info(sprintf('File %s is empty. Skip it.', $filename));
                continue;
            }

            $directory = basename(pathinfo($filename, PATHINFO_DIRNAME));
            $top       = key($content);

            if ($directory !== $top) {
                throw ConfigurationException::fileNotEqualsCurrentStage($directory, $top, $filename);
            }

            $this->logger->debug(sprintf('Config %s/%s [top=%s] is fine.', $directory, basename($filename), $top));

            $config = $this->arrayMergeRecursive($config, current($content));
        }

        return $config;
    }

    /**
     * Takes an env variable and returns default if not exist.
     *
     * @param string $variable
     * @param string $default
     *
     * @return string
     */
    private static function getEnvVariable(string $variable, string $default = ''): string
    {
        return getenv($variable) ?: $default;
    }

    /**
     * Works like array_merge_recursive_distinct,
     * but not merge sequential list.
     *
     * @param array ...$arrays
     *
     * @return array
     */
    private function arrayMergeRecursive(array ...$arrays): array
    {
        $base = array_shift($arrays);

        foreach ($arrays as $append) {
            if (!is_array($append)) {
                $append = [$append];
            }
            foreach ($append as $key => $value) {
                if (!array_key_exists($key, $base) && ! is_numeric($key)) {
                    $base[$key] = $value;
                    continue;
                }

                if ((is_array($value) || (isset($base[$key]) && is_array($base[$key]))) && $this->isAssoc($value)) {
                    $base[$key] = $this->arrayMergeRecursive(
                        (array) $base[$key],
                        (array) $value
                    );
                } else if (is_numeric($key)) {
                    if (!in_array($value, $base, true)) {
                        $base[] = $value;
                    }
                } else {
                    $base[$key] = $value;
                }
            }
        }

        return $base;
    }

    /**
     * Check if array is associative or sequential list.
     *
     * @param array $array
     *
     * @return bool
     */
    private function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return !array_is_list($array);
    }

    /**
     * Automatically find configuration in possible paths.
     *
     * @throws ConfigurationException
     *
     * @return string
     */
    private static function findDirectories(): string
    {
        if ($value = trim(self::getEnvVariable(self::CONFIG_PATH))) {
            // add env config path to top of possible locations.
            array_unshift(self::$possibleLocations, $value);
        }

        foreach (self::$possibleLocations as $path) {
            if (($location = realpath($path)) !== false) {
                return $location;
            }
        }

        throw ConfigurationException::autoFindConfigurationDirFailed(self::$possibleLocations);
    }
}
