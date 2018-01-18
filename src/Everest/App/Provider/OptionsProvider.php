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
use Everest\Container\FactoryProviderInterface;
use Everest\App\DelegateProviderInterface;
use Everest\App\Options;

class OptionsProvider implements FactoryProviderInterface, DelegateProviderInterface {

	private const STATE_IDLE = 0;
	private const STATE_INITIALIZED = 1;

	/**
	 * Provider state
	 * @var int
	 */
	
	private $state;

	/**
	 * Options instances
	 * @var array
	 */
	
	private $options;

	/**
	 * Initial options instance
	 * @var Everest\App\Options
	 */
	
	private $initialOptions;

	public function __construct(Options $initialOptions = null)
	{
		$this->state = self::STATE_IDLE;
		$this->options = [];
		$this->initialOptions = $initialOptions ?: new Options;
	}

	/**
	 * Adds new options to this provider
	 * @param Everest\App\Options $options
	 */
	
	public function add(Options $options, string $namespace = null)
	{
		if ($this->state === self::STATE_INITIALIZED) {
			throw new \RuntimeException('You cant add new options if provider is already initialized.');
		}

		$this->options[] = [$options, $namespace];
	}

	/**
	 * {@inheritDoc}
	 */
	
	public function getFactory()
	{
		$this->state = self::STATE_INITIALIZED;

		return [function(){
			return array_reduce($this->options, function($carry, $optionsAndNamespace) {
				[$options, $namespace] = $optionsAndNamespace;
				return $carry->merge($options, $namespace);
			}, $this->initialOptions);
		}];
	}

	/**
	 * {@inheritDoc}
	 */
	
	public function getDelegates() : array
	{
		return [
			'options' => function($options, string $namespace = null) {
				$this->add(Options::from($options), $namespace);
			}
		];
	}
}
