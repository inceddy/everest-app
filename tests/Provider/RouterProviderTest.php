<?php


use Everest\Http\Provider\RouterProvider;
use Everest\Http\Responses\Response;
use Everest\Http\Requests\ServerRequest;
use Everest\Http\Tests\WebTestCase;
use Everest\Http\Uri;

use Everest\Container\Container;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class RouterProviderTest extends WebTestCase {

	public function setUp()
	{
		$this->setupWebRequest();
	}

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

		$container->config(['RouterProvider', function($router) {
			$test = $this;
			$router->get('prefix/{id}', ['Request', 'RouteParameter', 'TestValue', function($request, $parameter, $testValue) use ($test) {
				$this->assertTrue(is_object($request));
				$test->assertEquals(['id' => 'test'], $parameter);
				$this->assertEquals('test-value', $testValue);

				return 'Not Empty Result';
			}]);
		}]);


		$container['Router']->handle($container->Request);
	}

	public function testDefaultHandlerAcceptsDependencies()
	{
		$container = $this->getContainer();

		$container->config(['RouterProvider', function($router) {
			$test = $this;
			$router->otherwise(['Request', 'Error', 'TestValue', function($request, $error, $testValue){
				$this->assertTrue(is_object($request));
				$this->assertNull($error);
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
