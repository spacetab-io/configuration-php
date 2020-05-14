<?php

declare(strict_types=1);

namespace Spacetab\Configuration\Exception;

use Exception;

class ConfigurationException extends Exception
{
    private const SPECIFICATION_URI = 'https://confluence.teamc.io/pages/viewpage.action?pageId=4227704';

    /**
     * ConfigurationException constructor.
     *
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct(sprintf("%s\n\nDocumentation available here: %s", $message, self::SPECIFICATION_URI));
    }

    /**
     * @return self
     */
    public static function operationNotAllowed(): self
    {
        return new self('Operation not allowed.');
    }

    /**
     * @param string $pattern
     * @param string $path
     * @param string $stage
     *
     * @return self
     */
    public static function filesNotFound(string $pattern, string $path, string $stage)
    {
        $one = "Files not found. Used glob pattern: {$pattern}.";
        $two = "CONFIG_PATH={$path} and STAGE={$stage} is correct?";

        return new self("{$one}\n{$two}");
    }

    /**
     * @param string $path
     *
     * @return self
     */
    public static function pathNotFound(string $path): self
    {
        return new self("Path [{$path}] not found.");
    }

    /**
     * @param string $stage
     * @param string $top
     * @param string $filename
     *
     * @return self
     */
    public static function fileNotEqualsCurrentStage(string $stage, string $top, string $filename): self
    {
        $one = "Developer error! STAGE [{$stage}] is not equals top of file [{$top}].";
        $two = "Please, fix the file [{$filename}].";

        return new self("{$one} {$two}");
    }

    /**
     * @param array<string> $possibleLocations
     *
     * @return self
     */
    public static function autoFindConfigurationDirFailed(array $possibleLocations): self
    {
       return new self(sprintf(
           'Configuration directory not found in known path\'s: %s',
           join(',', $possibleLocations)
       ));
    }
}
