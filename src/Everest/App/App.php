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

use Everest\Http\Requests\ServerRequest;
use Everest\Http\Requests\RequestInterface;
use Everest\Http\Responses\Response;
use Everest\App\Provider\OptionsProvider;
use Everest\App\Provider\RouterProvider;
use Everest\Container\Container;

use InvalidArgumentException;

class App extends Container {

	private $delegates;

	public function __construct(...$arguments)
	{
		parent::__construct(...$arguments);

		// Initialize delegates
		$this->delegates = [];

		// Define logger
		$this->Logger = null;
		// Define default error handler
		$this->factory('ErrorHandler', ['Logger', function($logger){
			return function(Exception $error) use ($logger) {
				if ($logger) {
					$logger->error(sprintf(
						'Unhandled exception occured: %s', 
						$error->getMessage()
					));
				}
				return new Response(500, 'Internal server error.');
			};
		}]);

		// Insert root options
		$this->provider('Options', new OptionsProvider(Options::from([
			'app_env'     => 'test',
			'app_version' => 'alpha'
		])));

		// Define router provider
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
			throw new \RuntimeException('Delegates can only be called during inital state.');
		}

		if (isset($this->delegates[$name])) {
			$this->config([function() use ($name, $arguments) {
				call_user_func_array($this->delegates[$name], $arguments);
			}]);

			return $this;
		}

		throw new \BadMethodCallException(sprintf('Unkown method %s.', $name));
	}


	/**
	 * Boots the container and handles the current request
	 * @return self
	 */
	
	public function run(RequestInterface $request, bool $catch = true)
	{
		$this->constant('Request', $request ?: ServerRequest::fromGlobals());
		$this->boot();

		try {		
			$response = $this->Router->handle($this->Request);
		}
		catch (\Exception $error) {
			if (!$catch) {
				throw $error;
			}
			$response = ($this->ErrorHandler)($error);
		}

		$response->send();

		return $this;
	}
}
