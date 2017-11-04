<?php


use Everest\Http\Tests\WebTestCase;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class ContainerTest extends WebTestCase {

	public function setUp()
	{
		$this->setupWebRequest();
	}

	public function testContainerExtension()
	{
		new Everest\App\Options;

		$container = new Everest\Container\Container;
		$container->constant('hello', 'world');
		$container->value('foo', 'bar');

		$container2 = new Everest\Container\Container;
		$container2->constant('hello2', 'world2');
		$container2->value('foo2', 'bar2');

		$app = new Everest\App\App($container, $container2);

		// Test Constant (without provider)
		$app->config(['hello', 'hello2', function($hello, $hello2){
			$this->assertEquals('world', $hello);
			$this->assertEquals('world2', $hello2);
		}]);

		// Test value (with provider)
		$app->boot();
		$this->assertEquals('bar', $app['foo']);
		$this->assertEquals('bar2', $app['foo2']);

	}
}