<?php

/*
 * This file is part of Everest.
 *
 * (c) 2017 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\App;

class Options {

	private $options;

	public static function from($options) : Options
	{
		if (is_array($options)) {
			return new self($options);
		}

		if (is_string($options) && is_readable($options)) {
			$type = (($pos = strrpos($options, '.')) !== -1) ? strtolower(substr($options, $pos + 1)) : null;

			switch ($type) {
				case 'php':
					return self::from(include $options);
				case 'json':
					return self::from(json_decode(file_get_contents($options), true));
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
			is_object($options) ? get_class($options) : gettype($options)
		));		
	}

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
					$options = $options->$segment;
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

	private static function mergeRecursive(array &$optionsOld, array &$optionsNew)
	{
		$merged = $optionsOld;

		foreach ($optionsNew as $name => $value) {
      if (is_array($value) && isset($merged[$name]) && is_array($merged[$name])) {
            $merged[$name] = self::mergeRecursive($merged[$name], $value);
      } else if (is_numeric($name)) {
        if (!in_array($value, $merged))
          $merged[] = $value;
      } else {
        $merged[$name] = $value;
      }
		}

		return $merged;
	}

	public function merge(Options $options) : Options
	{
		$this->options = self::mergeRecursive($this->options, $options->options);
		return $this;
	}
}
