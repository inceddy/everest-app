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
use Everest\Http\Requests\Request;
use Everest\Http\Requests\ServerRequests;

/**
 * The provider class for router object in an ieu\Container
 */


class RequestProvider {

	/**
	 * Custom request
	 * @var Everest\Http\Requests\Request|null
	 */
	
	private $request;

	/**
	 * The Request factory
	 * @var array
	 */
	
	public $factory;


	/**
	 * Provider delegates
	 * @var array
	 */
	
	public $delegates;


	public function __construct()
	{
		// Initialize factory
		$this->factory = [[$this, 'factory']];

		// Initialize delegates
		$this->delegates = [
			'defineRequest' => [$this, 'setRequest']
		];
	}

	/**
	 * Define custom request
	 * @param Everest\Http\Requests\Request|null $request
	 */
	
	public function setRequest(Request $request = null)
	{
		$this->request = $request;
	}


	/**
	 * The factory method that will be uses by the injector.
	 *
	 * @return Everest\Http\Request
	 */
	
	public function factory()
	{
		return $this->request ?: ServerRequest::fromGlobals();
	}
}

