<?php

namespace Everest\App\Provider;
use Everest\Container\Provider;
use LogicException;

use Twig_Environment;
use Twig_SimpleFilter;
use Twig_Extension;


/**
 * A provider for Twig.
 */

class TwigProvider extends Provider {

	/**
	 * Twig template loader instance
	 * @var string|Twig_LoaderInterface
	 */
	
	private $loader;


	/**
	 * The options cache
	 * @var array
	 */
	
	private $options = [];


	/**
	 * The filter cache
	 * @var array
	 */
	
	private $filters = [];


	/**
	 * The extension cache
	 * @var array
	 */
	
	private $extensions = [];


	/**
	 * Contructor
	 * Invokes a new TwigProvider
	 *
	 * @throws LogicException If the Twig_Environment class is not available
	 *
	 * @return self
	 * 
	 */
	
	public function __construct() 
	{
		if (!class_exists('Twig_Environment')) {
			throw new LogicException("The Twig package is not installed/available.");
		}

		$this->factory = ['Injector', [$this, 'factory']];
	}

	/**
	 * The factory method used by the injector
	 *
	 * @param  ieu\Container\Injector $injector  The injector
	 *
	 * @return Twig_Environmen                   The new setup template engine
	 * 
	 */
	
	public function factory($injector)
	{
		if (!isset($this->loader)) {
			throw new LogicException("The Twig loader is not set");
		}

		if (is_string($this->loader)) {
			$this->setLoader($injector->get($this->loader));
		}

		$environment = new Twig_Environment($this->loader, $this->options);

		// Add filters
		foreach ($this->filters as $filter) {
			$environment->addFilter($filter);
		}

		// Add extensions
		foreach ($this->extensions as $extension) {
			$environment->addExtension($extension);
		}

		return $environment;
	}


	/**
	 * Sets an option in the options cache
	 *
	 * @param string $key    The option name
	 * @param mixed  $value  The option value
	 *
	 * @return self
	 * 
	 */
	
	public function setOption($key, $value)
	{
		$this->options[$key] = $value;
		return $this;
	}


	/**
	 * Sets multiple options in the options cache
	 * 
	 * @param array $options  The name -> value option-paris to set
	 *
	 * @return self
	 * 
	 */
	
	public function setOptions(array $options)
	{
		foreach ($options as $key => $option) {
			$this->setOption($key, $option);
		}

		return $this;
	}


	/**
	 * Gets the options cache
	 *
	 * @return array  The options
	 * 
	 */
	
	public function getOptions()
	{
		return $this->options;
	}


	/**
	 * Sets the loader or the name of the loader-service for
	 * the Twig enviroment.
	 *
	 * @param string|Twig_LoaderInterface $loader  the loader or 
	 *                                             the name of the loader-service
	 * @return self
	 * 
	 */
	
	public function setLoader($loader) 
	{
		$this->loader = $loader;

		return $this;
	}


	/**
	 * Gets the loader, or the loader-name.
	 *
	 * @return string|Twig_LoaderInterface
	 * 
	 */
	
	public function getLoader()
	{
		return $this->loader;
	}


	/**
	 * Adds a new filter to the filter cache
	 *
	 * @param Twig_SimpleFilter $filter  The filter
	 *
	 * @return self
	 * 
	 */
	
	public function addFilter(Twig_SimpleFilter $filter)
	{
		$this->filters[] = $filter;

		return $this;
	}


	/**
	 * Gets the filter cache
	 *
	 * @return array  The filter cache
	 */
	
	public function getFilters()
	{
		return $this->filters;
	}


	/**
	 * Adds a new extension to the extension cache
	 *
	 * @param Twig_Extension $extension  The extension
	 *
	 * @return self
	 * 
	 */
	
	public function addExtension(Twig_Extension $extension)
	{
		$this->extensions[] = $extension;

		return $this;
	}


	/**
	 * Gets the extension cache
	 *
	 * @return array  The extension cache
	 */

	public function getExtensions()
	{
		return $this->extensions;
	}
}