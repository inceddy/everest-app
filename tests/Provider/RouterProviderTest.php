<?php


use Everest\App\Provider\RouterProvider;
use Everest\Http\Responses\Response;
use Everest\Http\Requests\RequestInterface;
use Everest\Http\Requests\ServerRequest;
use Everest\Http\Requests\Request;
use Everest\Http\Uri;

use Everest\Container\Container;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class RouterProviderTest extends \PHPUnit_Framework_TestCase {

	public function getContainer()
	{
		return (new Container)
		->factory('Request', [function(){
			// Mock request
			$request = $this->getMockBuilder(ServerRequest::CLASS)
				->disableOriginalConstructor()
				->getMock();

			$request->method('getUri')->willReturn(Uri::from('http://steingrebe.de/prefix/test'));
			$request->method('isMethod')->willReturn(true);
			return $request;
		}])
		->value('TestValue', 'test-value')
		->provider('Router', new RouterProvider);
	}

	public function testInstanceSetup()
	{
		$container = $this->getContainer();
		$this->assertInstanceOf(RouterProvider::CLASS, $container['Router']);
	}

	public function testRoutingAcceptsDependencies()
	{
		$container = $this->getContainer();
		$called = false;

		$container->config(['RouterProvider', function($router) use (&$called) {
			$router->get('prefix/{id}', ['Request', 'RouteParameter', 'TestValue', 
				function($request, $parameter, $testValue) use (&$called) {
					$called = true;

					$this->assertInstanceOf(RequestInterface::CLASS, $request);
					$this->assertEquals(['id' => 'test'], $parameter);
					$this->assertEquals('test-value', $testValue);
					return 'Not Empty Result';
				}
			]);
		}]);



		$container['Router']->handle($container->Request);
		$this->assertTrue($called);
	}

	public function testDefaultHandlerAcceptsDependencies()
	{
		$container = $this->getContainer();

		$container->config(['RouterProvider', function($router) {
			$router->otherwise(['Request', 'TestValue', function($request, $testValue){
				$this->assertInstanceOf(RequestInterface::CLASS, $request);
				$this->assertEquals('test-value', $testValue);

				return 'not-empty-result';
			}]);
		}]);

		$response = $container['Router']->handle($container->Request);

		$this->assertInstanceOf(Response::CLASS, $response);
	}

	public function testContextAcceptsDependencies()
	{
		$container = $this->getContainer();

		$container->config(['RouterProvider', function($router) {
			$test = $this;

			$router->context('prefix', ['TestValue', function(Router $router, $testValue){
				$this->assertEquals('test-value', $testValue);
			}]);

		}]);

		$response = $container['Router'];
	}
}
