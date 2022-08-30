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

namespace Everest\App\Provider;

use Everest\App\DelegateProviderInterface;
use Everest\Container\Container;
use Everest\Container\FactoryProviderInterface;
use Everest\Container\Injector;
use Everest\Http\Requests\ServerRequest;
use Everest\Http\Responses\Response;
use Everest\Http\Route;
use Everest\Http\Router;
use LogicException;

/**
 * The provider class for router object in an ieu\Container
 */


class RouterProvider extends Router implements FactoryProviderInterface, DelegateProviderInterface
{
    /**
     * The Router factory
     * @var array
     */
    public $factory;

    /**
     * The injector used to resolve depedencies
     * @var ieu\Container\Injector
     */
    private $injector;

    /**
     * Whether this provider is constructed or not
     */
    private bool $constructed = false;


    public function getFactory()
    {
        return ['Injector', $this->factory(...)];
    }


    public function getDelegates(): array
    {
        return [
            'context' => $this->context(...),
            // Routing
            'request' => $this->request(...),
            'get' => $this->get(...),
            'post' => $this->post(...),
            'put' => $this->put(...),
            'patch' => $this->patch(...),
            'delete' => $this->delete(...),
            'any' => $this->any(...),
            'otherwise' => $this->otherwise(...),
            'error' => [$this, 'error'],
            // Middleware
            'before' => $this->before(...),
            'after' => $this->after(...),
        ];
    }

    /**
     * The factory method that will be uses by the injector.
     *
     * @param  Injector $injector The injector
     *
     * @return Everest\Http\RouterProvider
     */
    public function factory(Injector $injector)
    {
        $this->injector = $injector;
        $this->constructed = true;

        return $this;
    }

    /**
     * Overload before to enable predefined middlewares
     * and middleware wrapped in dependecy arrays.
     *
     * {@inheritDoc}
     */
    public function before(...$middlewares)
    {
        return parent::before(...array_map(function ($middleware) {
            if (is_callable($middleware)) {
                return $middleware;
            }

            return function (...$middlewareArgs) use ($middleware) {
                // Predefined middleware
                if (is_string($middleware)) {
                    return ($this->injector->get($middleware))(...$middlewareArgs);
                }
                // Middleware with dependencies
                return $this->injector->invoke(
                    Container::getDependencyArray($middleware),
                    [],
                    $middlewareArgs
                );
            };
        }, $middlewares));
    }

    /**
     * Overload after to enable predefined middlewares
     * and middleware wrapped in dependecy arrays.
     *
     * {@inheritDoc}
     */
    public function after(...$middlewares)
    {
        return parent::after(...array_map(function ($middleware) {
            if (is_callable($middleware)) {
                return $middleware;
            }

            return function (...$middlewareArgs) use ($middleware) {
                // Predefined middleware
                if (is_string($middleware)) {
                    return ($this->injector->get($middleware))(...$middlewareArgs);
                }
                // Middleware with dependencies
                return $this->injector->invoke(
                    Container::getDependencyArray($middleware),
                    [],
                    $middlewareArgs
                );
            };
        }, $middlewares));
    }

    /**
     * Overload route to wrap route handler in a dependency array
     *
     * {@inheritDoc}
     */
    public function route(Route $route, $handler)
    {
        // No dependency resolving from parameters!
        if (is_callable($handler)) {
            return parent::route($route, $handler);
        }

        return parent::route($route, fn (...$RequestAndMiddlewareArgs) => $this->injector->invoke(
            Container::getDependencyArray($handler),
            [],
            $RequestAndMiddlewareArgs
        ));
    }

    /**
     * Overload context to wrap invoker in a dependency array
     *
     * {@inheritDoc}
     */
    public function context($prefix, $invoker = null)
    {
        if ($invoker === null) {
            $invoker = $prefix;
            $prefix = '';
        }

        // No dependency resolving from parameters!
        if (is_callable($invoker)) {
            return parent::context($prefix, $invoker);
        }

        return parent::context($prefix, fn () => $this->injector->invoke(
            Container::getDependencyArray($invoker),
            [],
            [$this]
        ));
    }

    /**
     * Overload otherwise to wrap default handlers in a dependency array
     *
     * {@inheritDoc}
     */
    public function otherwise($handler)
    {
        // No dependency resolving from parameters!
        if (is_callable($handler)) {
            return parent::otherwise($handler);
        }

        return parent::otherwise(fn ($request, $orgRequest) => $this->injector->invoke(
            Container::getDependencyArray($handler),
            [
                'Request' => $request,
                'OriginalRequest' => $orgRequest,
            ]
        ));
    }

    /**
     * Overload error to wrap error handlers in a dependency array
     *
     * {@inheritDoc}
     */
    public function error($handler)
    {
        // No dependency resolving from parameters!
        if (is_callable($handler)) {
            return parent::error($handler);
        }

        return parent::error(fn ($error, $request) => $this->injector->invoke(
            Container::getDependencyArray($handler),
            [
                'Error' => $error,
                'Request' => $request,
            ]
        ));
    }

    /**
     * Overload handle to ensure that this method is
     * only called on an instance and not on the provider.
     *
     * {@inheritDoc}
     */
    public function handle(ServerRequest $request): Response
    {
        if (! $this->constructed) {
            throw new LogicException('You cant call handle in config state.');
        }

        return parent::handle($request);
    }
}
