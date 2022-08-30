<?php

declare(strict_types=1);


use Everest\App\Provider\RouterProvider;
use Everest\Container\Container;
use Everest\Http\Requests\Request;
use Everest\Http\Requests\RequestInterface;
use Everest\Http\Requests\ServerRequest;
use Everest\Http\Responses\Response;
use Everest\Http\Router;

use Everest\Http\Uri;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class RouterProviderTest extends \PHPUnit\Framework\TestCase
{
    public function getContainer()
    {
        return (new Container())
            ->factory('Request', [function () {
            return new ServerRequest(
                ServerRequest::HTTP_ALL,
                Uri::from('http://steingrebe.de/prefix/test?some=value#hash')
            );
        }])
            ->value('TestValue', 'test-value')
            ->provider('Router', new RouterProvider());
    }

    public function testInstanceSetup()
    {
        $container = $this->getContainer();
        $this->assertInstanceOf(RouterProvider::class, $container['Router']);
    }

    public function testRoutingAcceptsDependencies()
    {
        $container = $this->getContainer();
        $called = false;

        $container->config(['RouterProvider', function ($router) use (&$called) {
            $router->get('prefix/{id}', ['TestValue',
                function (RequestInterface $request, $testValue) use (&$called) {
                    $called = true;
                    $this->assertEquals([
                        'id' => 'test',
                    ], $request->getAttribute('parameter'));
                    $this->assertEquals('test-value', $testValue);
                    return 'Not Empty Result';
                },
            ]);
        }]);

        $container['Router']->handle($container->Request);
        $this->assertTrue($called);
    }

    public function testDefaultHandlerAcceptsDependencies()
    {
        $container = $this->getContainer();

        $container->config(['RouterProvider', function ($router) {
            $router->otherwise(['Request', 'TestValue', function ($request, $testValue) {
                $this->assertInstanceOf(RequestInterface::class, $request);
                $this->assertEquals('test-value', $testValue);

                return 'not-empty-result';
            }]);
        }]);

        $response = $container['Router']->handle($container->Request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testContextAcceptsDependencies()
    {
        $container = $this->getContainer();

        $container->config(['RouterProvider', function ($router) {
            $test = $this;

            $router->context('prefix', ['TestValue', function (Router $router, $testValue) {
                $this->assertEquals('test-value', $testValue);
                $router->get('/', function () {
                    return '';
                });
            }]);
        }]);

        $response = $container['Router']->handle(
            new ServerRequest(ServerRequest::HTTP_GET, Uri::from('http://a.de/prefix'))
        );
    }

    public function testDelegates()
    {
        $count = 0;

        $λ = function () use (&$count) {
            $count++;
            return '';
        };

        $app = new Everest\App\App();
        $app->context('some-context', $λ);
        $app->request('/', ServerRequest::HTTP_POST | ServerRequest::HTTP_DELETE, $λ);
        $app->get('/', $λ);
        $app->post('/', $λ);
        $app->put('/', $λ);
        $app->patch('/', $λ);
        $app->delete('/', $λ);
        $app->any('/', $λ);
        $app->otherwise($λ);

        $app->boot();
        $app->container()['Router']->handle(
            new ServerRequest(
                ServerRequest::HTTP_GET,
                Uri::from('http://a.de/')
            )
        );

        $this->assertEquals(1, $count);
    }

    public function testMiddleware()
    {
        $called = true;
        $container = $this->getContainer();

        $container->value('B', 'B');
        $container->value('C', 'C');
        $container->factory('Middleware', ['B', function ($b) use (&$called) {
            return function (\Closure $next, Request $request) use ($b, &$called) {
                $called &= true;
                return $next($request);
            };
        }]);

        $container->config(['RouterProvider', function ($router) use (&$called) {
            // Classic middleware
            $router->before(function (\Closure $next, Request $request) use (&$called) {
                $called &= true;
                return $next($request);
            });

            // Predefined middleware
            $router->before('Middleware');

            // Middleware with dependencies
            $router->before(['C', function (\Closure $next, Request $request, string $c) use (&$called) {
                $called &= true;
                return $next($request);
            }]);

            $router->otherwise(function () use (&$called) {
                $called &= true;
                return 'ok';
            });
        }]);

        $response = $container['Router']->handle($container->Request);

        $this->assertTrue((bool) $called);
    }

    public function testMiddlewareRequestModification()
    {
        $container = $this->getContainer();
        $container['Middleware'] = function (\Closure $next, ServerRequest $request) {
            return $next($request->withUri(Uri::from('http://foo.bar/foo_bar')));
        };
        $container->config(['RouterProvider', function ($router) use (&$called) {
            $router->before('Middleware');
            $router->otherwise(['Request', function ($request) {
                $this->assertSame('foo_bar', $request->getUri()->getPath());
                return 'ok';
            }]);
        }]);
        $container['Router']->handle($container->Request);
    }
}
