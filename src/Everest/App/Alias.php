<?php

declare(strict_types=1);

/*
 * This file is part of Everest.
 *
 * (c) 2019 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\App;

use InvalidArgumentException;

/**
 * Aliases an app component
 */
abstract class Alias
{
    /**
     * The app whose components are aliased
     */
    private static ?App $app = null;

    private static array $components = [];

    /**
     * Calls method on the aliased component
     *
     * @param  string $method
     * @param  array $args
     *
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        $component = self::component();
        return $component->{$method}(...$args);
    }

    public static function reset(bool $components = true, bool $app = true)
    {
        if ($components) {
            self::$components = [];
        }

        if ($app) {
            self::$app = null;
        }
    }

    /**
     * Sets the app whose components are aliased
     */
    public static function setApp(App $app): void
    {
        self::$app = $app;
    }

    public static function swap($component): void
    {
        $current = self::component();

        if (gettype($component) !== gettype($current)) {
            throw new InvalidArgumentException(sprintf(
                'Cant swap alias of type %s with new component of type %s.',
                gettype($current),
                gettype($component)
            ));
        }

        if (is_object($current) && $current::class !== $component::class) {
            throw new InvalidArgumentException(sprintf(
                'Cant swap alias of class %s with new component of class %s.',
                $current::class,
                $component::class
            ));
        }

        $name = static::getName();
        self::$components[$name] = $component;
    }

    /**
     * Returns the component of this alias
     * @return mixed
     */
    public static function component()
    {
        $name = static::getName();
        return self::$components[$name] ??
               self::$components[$name] = self::$app->container()[$name];
    }

    /**
     * Returns the component name and
     * must be overloaded by each alias
     *
     * @return string
     */
    abstract protected static function getName(): string;
}
