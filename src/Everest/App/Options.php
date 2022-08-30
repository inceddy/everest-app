<?php

declare(strict_types=1);

/*
 * This file is part of Everest.
 *
 * (c) 2018 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\App;

class Options
{
    /**
     * Options
     */
    private readonly array $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function __invoke(string $path, $default = null)
    {
        $options = $this->options;
        foreach (explode('.', $path) as $segment) {
            switch (true) {
                case is_array($options) && isset($options[$segment]):
                    $options = $options[$segment];
                    break;

                case is_object($options) && property_exists($options, $segment):
                    $options = $options->{$segment};
                    break;

                default:
                    if ($default !== null) {
                        return $default;
                    }
                    throw new \InvalidArgumentException(sprintf('Given path \'%s\' does not match any option', $path));
            }
        }

        return $options;
    }

    /**
     * Factory method for ini files
     *
     * @throws InvalidArgumentException
     *   If ini file path is not readable
     *
     * @param string $path
     *   The ini file path
     * @param bool $processSections
     *   Whether or not to process ini sections (default `true`)
     * @param int $scannerMode
     *   The scanner mode bitmask
     */
    public static function fromIniFile(
        string $path,
        bool $processSections = true,
        int $scannerMode = INI_SCANNER_NORMAL
    ): self {
        return self::fromIniString(
            self::readFile($path),
            $processSections
        );
    }

    /**
     * Factory method for ini strings
     *
     * @throws InvalidArgumentException
     *   If ini string is invalid
     *
     * @param string $string
     *   The ini string
     * @param bool $processSections
     *   Whether or not to process ini sections (default `true`)
     * @param int $scannerMode
     *   The scanner mode bitmask
     */
    public static function fromIniString(
        string $string,
        bool $processSections = true,
        int $scannerMode = INI_SCANNER_NORMAL
    ): self {
        if (! $options = parse_ini_string($string, $processSections, $scannerMode)) {
            throw new \InvalidArgumentException('Invalid ini string');
        }

        return new self($options);
    }

    /**
     * Factory method for json files
     *
     * @throws InvalidArgumentException
     *   If json file path is not readable
     *
     * @param string $path
     *   The json file path
     * @param bool $assoc
     *   Whether or not to parse to array (default `true`)
     */
    public static function fromJsonFile(string $path, bool $assoc = true): self
    {
        return self::fromJsonString(
            self::readFile($path),
            $assoc
        );
    }

    /**
     * Factory method for json strings
     *
     * @throws InvalidArgumentException
     *   If json string is invalid
     *
     * @param bool $assoc
     *   Whether or not to parse to array (default `true`)
     */
    public static function fromJsonString(string $string, bool $assoc = true): self
    {
        if (! $options = @json_decode($string, $assoc, 512, JSON_THROW_ON_ERROR)) {
            throw new \InvalidArgumentException('Invalid json string');
        }

        return new self($options);
    }

    /**
     * General factory method
     *
     * @param  mixed $options
     *   The options input to parse
     */
    public static function from(mixed $options, ...$args): self
    {
        if (is_array($options)) {
            return new self($options);
        }

        if (is_string($options) && is_readable($options)) {
            $type = (($pos = strrpos($options, '.')) !== false) ? strtolower(substr($options, $pos + 1)) : null;

            switch ($type) {
                case 'php':
                    return self::from(include $options);
                case 'json':
                    return self::fromJsonFile($options, ...$args);
                case 'ini':
                    return self::fromIniFile($options, ...$args);
                case null:
                    throw new \InvalidArgumentException('No file extension specified');
                default:
                    throw new \InvalidArgumentException(sprintf('Unknown file extension %s.', $type));
            }
        }

        if (is_object($options)) {
            return new self((array) $options);
        }

        throw new \InvalidArgumentException(sprintf(
            'Unable to cast %s to Options',
            get_debug_type($options)
        ));
    }

    public function merge(self $options, string $namespace = null): self
    {
        $old = $this->options;
        $new = $namespace ? [
            $namespace => $options->options,
        ] : $options->options;

        return new self(
            self::mergeRecursive($old, $new)
        );
    }

    /**
     * Helper method for recursive merging of arrays
     */
    private static function mergeRecursive(array $optionsOld, array $optionsNew): array
    {
        $merged = $optionsOld;

        foreach ($optionsNew as $name => $value) {
            if (is_array($value) && isset($merged[$name]) && is_array($merged[$name])) {
                $merged[$name] = self::mergeRecursive($merged[$name], $value);
            } elseif (is_numeric($name)) {
                if (! in_array($value, $merged, true)) {
                    $merged[] = $value;
                }
            } else {
                $merged[$name] = $value;
            }
        }

        return $merged;
    }

    /**
     * Returns file content if readable
     *
     * @throws InvalidArgumentException
     *   If path is not readable
     *
     * @param  string $path
     *   The file path
     */
    private static function readFile(string $path): string
    {
        if (! is_readable($path) || false === ($content = file_get_contents($path))) {
            throw new \InvalidArgumentException(sprintf('Cant read file with path %s', $path));
        }

        return $content;
    }
}
