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
use Everest\App\Provider\SessionProvider;
use Everest\Container\Container;
use Everest\Container\FactoryProviderInterface;

class App extends Container {

	/**
	 * Deletegated provider methods
	 * @var array
	 */
	
	private $delegates;

	public function __construct(...$arguments)
	{
		parent::__construct(...$arguments);

		// Initialize delegates
		$this->delegates = [];

		// Define logger
		$this->value('Logger', null);

		// Define default error handler
		$this->factory('ErrorHandler', ['Logger', function($logger){
			return function(\Throwable $error) use ($logger) {
				if ($logger) {
					$logger->error(sprintf(
						'Unhandled exception occured: %s', 
						$error->getMessage()
					));
				}
				return new Response('Internal server error.', 500);
			};
		}]);

		// Define options provider
		$this->provider('Options', new OptionsProvider(Options::from([
			'app_env'     => 'test',
			'app_version' => 'alpha'
		])));

		// Define session provider
		$this->provider('Session', new SessionProvider);

		// Define router provider
		$this->provider('Router', new RouterProvider);
	}

	/**
	 * Overload provider method to catch providers with delegates.
	 * 
	 * {@inheritDoc}
	 */
	
	public function provider(string $name, FactoryProviderInterface $provider)
	{
		parent::provider($name, $provider);

		if ($provider instanceof DelegateProviderInterface) {
			$this->delegates = array_merge($this->delegates, $provider->getDelegates());
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
			throw new \RuntimeException(
				'Delegates can only be called while container is in inital state.'
			);
		}

		if (isset($this->delegates[$name])) {
			$this->config([function() use ($name, $arguments) {
				call_user_func_array($this->delegates[$name], $arguments);
			}]);

			return $this;
		}

		throw new \BadMethodCallException(sprintf('Unkown method %s.', $name));
	}

	public function boot()
	{
		parent::boot();
		Alias::setApp($this);
	}


	/**
	 * Boots the container and handles the given or global request
	 * @return self
	 */
	
	public function run(RequestInterface $request = null, bool $catch = true)
	{
		$this->constant('Request', $request ?: ServerRequest::fromGlobals());
		$this->boot();

		try {		
			$response = $this->Router->handle($this->Request);
		}
		catch (\Throwable $error) {
			if (!$catch) {
				throw $error;
			}
			$response = ($this->ErrorHandler)($error);
		}

		$response->send();

		return $this;
	}
}
