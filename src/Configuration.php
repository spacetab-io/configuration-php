<?php declare(strict_types=1);

namespace Spacetab\Configuration;

use ArrayAccess;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Configuration
 *
 * @package Spacetab\Configuration
 */
final class Configuration implements ConfigurationInterface, ArrayAccess, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Possible path's of configuration.
     *
     * @var array
     */
    private static $possibleLocations = [
        '/app/configuration',
        '/configuration',
        './configuration',
        __DIR__ . '/../configuration',
    ];

    /**
     * Name of CONFIG_PATH variable
     */
    private const CONFIG_PATH = 'CONFIG_PATH';

    /**
     * Name of stage ENV variable
     */
    private const STAGE = 'STAGE';

    /**
     * Default configuration stage
     */
    private const DEFAULT_STAGE = 'defaults';

    /**
     * Default config location.
     */
    private const DEFAULT_CONFIG_PATH = '/app/configuration';

    /**
     * Config tree goes here
     */
    private $config;

    /**
     * @var null|string
     */
    private $path;

    /**
     * @var null|string
     */
    private $stage;

    /**
     * Configuration constructor.
     *
     * @param null|string $path
     * @param null|string $stage
     */
    public function __construct(?string $path = null, ?string $stage = null)
    {
        if (null === $path) {
            $this->setPath($this->getEnvVariable(self::CONFIG_PATH, self::DEFAULT_CONFIG_PATH));
        } else {
            $this->setPath($path);
        }

        if (null === $stage) {
            $this->setStage($this->getEnvVariable(self::STAGE, self::DEFAULT_STAGE));
        } else {
            $this->setStage($stage);
        }

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
     * @return Configuration
     */
    public static function auto(?string $stage = null)
    {
        return new Configuration(self::findDirectories(), $stage);
    }

    /**
     * Get's a value from config by dot notation
     * E.g get('x.y', 'foo') => returns the value of $config['x']['y']
     * And if not exist, return 'foo'
     *
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $config = $this->config;

        array_map(function ($key) use (&$config, $default) {
            $config = $config[$key] ?? $default;
        }, explode('.', $key));

        return $config;
    }

    /**
     * Gets all the tree config
     *
     * @return mixed
     */
    public function all()
    {
        return $this->config;
    }

    /**
     * Set the configuration path
     *
     * @param null|string $path
     * @return Configuration
     */
    public function setPath(?string $path): Configuration
    {
        $this->path = realpath($path) ?: $path;

        return $this;
    }

    /**
     * Get the configuration path
     *
     * @return null|string
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @param null|string $stage
     * @return Configuration
     */
    public function setStage(?string $stage): Configuration
    {
        $this->stage = $stage;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getStage(): ?string
    {
        return $this->stage;
    }

    /**
     * Whether a offset exists
     *
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
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
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
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
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        throw new LogicException('Not allowed here.');
    }

    /**
     * Offset to unset
     *
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        throw new LogicException('Not allowed here.');
    }

    /**
     * Initialize all the magic down here
     */
    public function load(): Configuration
    {
        $this->logger->info(self::CONFIG_PATH . ' = ' . $this->getPath());
        $this->logger->info(self::STAGE . ' = ' . $this->getStage());

        if ($this->getPath() !== self::DEFAULT_CONFIG_PATH) {
            $message = 'Please use default [%s] configuration location instead of [%s]. If you use configuration locally, ignore this message.';
            $this->logger->warning(sprintf($message, self::DEFAULT_CONFIG_PATH, $this->getPath()));
        }

        $second = $this->getStage() !== self::DEFAULT_STAGE
            ? $this->parseConfiguration($this->getStage())
            : [];

        $this->config = $this->arrayMergeRecursive(
            $this->parseConfiguration(),
            $second
        );

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
     * Parses configuration and makes a tree of it
     *
     * @param string $stage
     * @return array
     */
    private function parseConfiguration(string $stage = self::DEFAULT_STAGE)
    {
        $pattern = $this->getPath() . '/' . $stage . '/*.yaml';
        $files   = glob($pattern, GLOB_NOSORT | GLOB_ERR);

        if ($files === false || count($files) < 1) {
            $message = "Glob does not walk to files, pattern: {$pattern}. Path is correct?";
            throw new InvalidArgumentException($message);
        }

        $this->logger->debug('Following config files found:', $files);

        $config = [];
        foreach ($files as $filename) {
            $content   = Yaml::parseFile($filename);
            $directory = basename(pathinfo($filename, PATHINFO_DIRNAME));
            $top       = key($content);

            if ($directory !== $top) {
                $message = 'Invalid! Stage of config directory [%s] is not equals top of yaml content [%s].';
                throw new InvalidArgumentException(sprintf($message, $directory, $top));
            }

            $this->logger->debug(sprintf('Config %s/%s [top=%s] is fine.', $directory, basename($filename), $top));

            $config = $this->arrayMergeRecursive($config, current($content));
        }

        return $config;
    }

    /**
     * Takes an env variable and returns default if not exist
     *
     * @param string $variable
     * @param string $default
     * @return string
     */
    private static function getEnvVariable(string $variable, string $default = '')
    {
        return getenv($variable) ?: $default;
    }

    /**
     * Works like array_merge_recursive_distinct,
     * but not merge sequential list.
     *
     * @param array ...$arrays
     * @return array|mixed
     */
    private function arrayMergeRecursive(array ...$arrays)
    {
        $base = array_shift($arrays);
        if ( ! is_array($base)) {
            $base = empty($base) ? [] : [$base];
        }

        foreach ($arrays as $append) {
            if ( ! is_array($append)) {
                $append = [$append];
            }
            foreach ($append as $key => $value) {
                if ( ! array_key_exists($key, $base) && ! is_numeric($key)) {
                    $base[$key] = $append[$key];
                    continue;
                }

                if ((is_array($value) || (isset($base[$key]) && is_array($base[$key]))) && $this->isAssoc($value)) {
                    $base[$key] = $this->arrayMergeRecursive(
                        (array) $base[$key],
                        (array) $append[$key]
                    );
                } else {
                    if (is_numeric($key)) {
                        if ( ! in_array($value, $base)) {
                            $base[] = $value;
                        }
                    } else {
                        $base[$key] = $value;
                    }
                }
            }
        }

        return $base;
    }

    /**
     * Check if array is associative or sequential list.
     *
     * @param array $array
     * @return bool
     */
    private function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Automatically find configuration in possible paths.
     *
     * @return string
     */
    private static function findDirectories(): string
    {
        if ($value = trim((string) self::getEnvVariable(self::CONFIG_PATH))) {
            // add env config path to top of possible locations.
            array_unshift(self::$possibleLocations, $value);
        }

        foreach (self::$possibleLocations as $path) {
            if (($location = realpath($path)) !== false) {
                return $location;
            }
        }

        throw new LogicException(sprintf(
            'Configuration directory not found in known path\'s: %s',
            join(',', self::$possibleLocations)
        ));
    }
}
