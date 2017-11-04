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

use Everest\Http\Provider\RouterProvider;
use Everest\Http\Requests\ServerRequest;
use Everest\Http\Responses\Response;

use Everest\App\Provider\OptionsProvider;

use Everest\Container\Container;

use InvalidArgumentException;

class App extends Container {

	private $delegates;

	public function __construct(...$arguments)
	{
		parent::__construct(...$arguments);

		// Initialize delegates
		$this->delegates = [];

		// Insert reuqest as constant
		$this->constant('Request', ServerRequest::fromGlobals());

		// Insert root options
		$this->provider('Options', new OptionsProvider(Options::from([
			'app_env'     => 'test',
			'app_version' => 'alpha'
		])));

		// Insert router provider
		$this->provider('Router', new RouterProvider);
	}

	public function provider(string $name, $provider)
	{
		parent::provider($name, $provider);

		if (property_exists($provider, 'delegates')) {
			$this->delegates = array_merge($this->delegates, $provider->delegates);
		}

		return $this;
	}

	/**
	 * Alias for controller services.
	 * The controller providers can be accessed by `controllernameController`.
	 *
	 * @param  string $name
	 *    The name of the controller service
	 * @param  array  $dependenciesAndClassname
	 *    The dependencies and the classnmae of the controller
	 *
	 * @return self
	 * 
	 */
	
	public function controller(string $name, $dependenciesAndClassname) 
	{
		return $this->service($name . 'Controller', $dependenciesAndClassname);
	}


	/**
	 * Merges new options into this app
	 *
	 * @param mixed $options
	 *    The new options object to merge
	 *    
	 * @return self
	 */
	
	public function options($options)
	{
		$options = Options::from($options);
		
		$this->config(['OptionsProvider', function($optionsProvider) use ($options) {
			$optionsProvider->add($options);
		}]);

		return $this;
	}


	/**
	 * Handle provider delegates
	 *
	 * @param  string $name
	 *    The delegate method name
	 * @param  array $arguments
	 *    The arguments to call the delegate method with
	 *
	 * @return self
	 */
	
	public function __call($name, $arguments)
	{
		if ($this->state !== self::STATE_INITIAL) {
			throw new \RuntimeException('Delegates can only be called during inital container state.');
		}

		if (isset($this->delegates[$name])) {
			$delegate = $this->delegates[$name];
			$this->config([function() use ($delegate, $arguments) {
				call_user_func_array($delegate, $arguments);
			}]);

			return $this;
		}

		throw new \BadMethodCallException(sprintf('Unkown method %s.', $name));
	}


	/**
	 * Boots the container and handles the current request by
	 * @return self
	 */
	
	public function run()
	{
		$this->boot();
		
		if (null === $response = $this->Router->handle($this->Request)) {
			$response = new Response('Internal server error', 500);
		}

		$response->send();

		return $this;
	}
}
