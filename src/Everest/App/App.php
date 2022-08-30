<?php

declare(strict_types=1);

/*
 * This file is part of Everest.
 *
 * (c) 2017 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\App;

use ArrayAccess;
use BadMethodCallException;
use Everest\App\Provider\OptionsProvider;
use Everest\App\Provider\RouterProvider;
use Everest\App\Provider\SessionProvider;
use Everest\Container\Container;
use Everest\Container\FactoryProviderInterface;
use Everest\Http\Requests\RequestInterface;
use Everest\Http\Requests\ServerRequest;
use Everest\Http\Responses\Response;
use Throwable;

class App implements ArrayAccess
{
    /**
     * Deletegated provider methods
     */
    private array $delegates;

    /**
     * Dependency container wrapped by this app
     */
    private readonly Container $container;

    public function __construct(...$arguments)
    {
        // Initialize delegates
        $this->delegates = [];

        // Initialize container
        $this->container = new Container(...$arguments);

        // Define App
        $this->value('App', $this);
        // Define Logger
        $this->value('Logger', null);
        // Define default error handler
        $this->factory('ErrorHandler', [
            'Logger',
            fn ($logger) => function (Throwable $error) use ($logger) {
                if ($logger) {
                    $logger->error(sprintf(
                        'Unhandled exception occured: %s',
                        $error->getMessage()
                    ));
                }
                return new Response('Internal server error.', 500);
            },
        ]);

        // Define options provider
        $this->provider('Options', new OptionsProvider(Options::from([
            'app_env' => 'test',
            'app_version' => 'alpha',
        ])));

        // Define session provider
        $this->provider('Session', new SessionProvider());

        // Define router provider
        $this->provider('Router', new RouterProvider());
    }


    public function __set(string $name, $value)
    {
        return $this->container->value($name, $value);
    }


    public function __get(string $name)
    {
        return $this->container->offsetGet($name);
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
        /*
        if ($this->state !== self::STATE_INITIAL) {
            throw new \RuntimeException(
                'Delegates can only be called while container is in inital state.'
            );
        }
        */

        if (isset($this->delegates[$name])) {
            $this->config([function () use ($name, $arguments) {
                call_user_func_array($this->delegates[$name], $arguments);
            }]);

            return $this;
        }

        throw new BadMethodCallException(sprintf(
            'Unkown delegate method %s. Use one of %s',
            $name,
            implode(', ', array_keys($this->delegates))
        ));
    }

    /**
     * Returns the dependency container of this app
     */
    public function container(): Container
    {
        return $this->container;
    }

    /**
     * Overload provider method to catch providers with delegates.
     *
     * {@inheritDoc}
     */
    public function provider(string $name, FactoryProviderInterface $provider)
    {
        $this->container->provider($name, $provider);

        if ($provider instanceof DelegateProviderInterface) {
            $this->delegates = array_merge($this->delegates, $provider->getDelegates());
        }

        return $this;
    }


    public function decorator($name, $decorator)
    {
        $this->container();
        return $this;
    }


    public function service($name, $service)
    {
        $this->container->service($name, $service);
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
     */
    public function controller(string $name, $dependenciesAndClassname)
    {
        return $this->container->service($name . 'Controller', $dependenciesAndClassname);
    }


    public function import(Container $container, string $prefix = null)
    {
        $this->container->import($container, $prefix);
        return $this;
    }


    public function factory(string $name, $factory)
    {
        $this->container->factory($name, $factory);
        return $this;
    }


    public function value(string $name, $value)
    {
        $this->container->value($name, $value);
        return $this;
    }


    public function constant(string $name, $value)
    {
        $this->container->constant($name, $value);
        return $this;
    }


    public function config($config)
    {
        $this->container->config($config);
        return $this;
    }

    public function boot()
    {
        $this->container->boot();
        Alias::setApp($this);
    }

    /**
     * Boots the container and handles the given or global request
     * @return self
     */
    public function run(RequestInterface $request = null, bool $catch = true)
    {
        $this->container->constant('Request', $request ?: ServerRequest::fromGlobals());
        $this->boot();

        try {
            $response = $this->container['Router']->handle($this->container['Request']);
        } catch (Throwable $error) {
            if (! $catch) {
                throw $error;
            }
            $response = ($this->container['ErrorHandler'])($error);
        }

        $response->send();

        return $this;
    }

    public function offsetGet(mixed $key): mixed
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated sice 1.4.0', E_USER_DEPRECATED);
        return $this->container->offsetGet($key);
    }

    public function offsetSet(mixed $key, mixed $value): void
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated sice 1.4.0', E_USER_DEPRECATED);
        $this->container->offsetSet($key, $value);
    }

    public function offsetExists(mixed $name): bool
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated sice 1.4.0', E_USER_DEPRECATED);
        return $this->container->offsetExists($name);
    }

    public function offsetUnset(mixed $name): void
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated sice 1.4.0', E_USER_DEPRECATED);
        $this->container->offsetUnset($name);
    }
}
