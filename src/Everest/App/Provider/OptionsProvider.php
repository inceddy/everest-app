<?php

/*
 * This file is part of Everest.
 *
 * (c) 2017 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\App\Provider;
use Everest\App\Options;

class OptionsProvider {

	public $factory;

	private $options;

	private $initialOptions;

	public function __construct(Options $initialOptions = null)
	{
		$this->options = [];
		$this->initialOptions = $initialOptions ?: new Options;
		$this->factory = [$this, 'factory'];
	}

	public function add(Options $options)
	{
		$this->options[] = $options;
	}

	public function factory()
	{
		return array_reduce($this->options, function($carry, $options) {
			return $carry->merge($options);
		}, $this->initialOptions);
	}
}