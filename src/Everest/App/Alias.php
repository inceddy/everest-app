<?php

/*
 * This file is part of Everest.
 *
 * (c) 2019 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\App;

/**
 * Aliases an app component
 */

abstract class Alias {
	
	/**
	 * The app whose components are aliased
	 * @var App
	 */
	
	private static $app;

	private static $components = [];

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
	 *
	 * @param  App $app
	 * @return void
	 */
	
	public static function setApp(App $app) : void
	{
		self::$app = $app;
	}

	/**
	 * Returns the component name and 
	 * must be overloaded by each alias
	 *
	 * @return string
	 */
	
	protected static function getName() : string 
	{
		throw new \LogicException('Alias::getName not yet implemented!');
	}

	public static function swap($component) : void
	{
		$current = self::component();

		if (gettype($component) !== gettype($current)) {
			throw new \InvalidArgumentException(sprintf(
				'Cant swap alias of type %s with new component of type %s.', 
				gettype($current),
				gettype($component)
			));			
		}

		if (is_object($current) && get_class($current) !== get_class($component)) {
			throw new \InvalidArgumentException(sprintf(
				'Cant swap alias of class %s with new component of class %s.', 
				get_class($current),
				get_class($component)
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
		       self::$components[$name] = self::$app[$name];
	}

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
		return $component->$method(... $args);
	}
}
