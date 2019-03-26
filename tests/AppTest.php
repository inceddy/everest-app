<?php


/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class AppTest extends \PHPUnit\Framework\TestCase {

	public function testContainerExtension()
	{
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

	public function testContainerImport()
	{
		$called = false;

		$container = new Everest\Container\Container;
		$container->constant('hello', 'world');
		$container->value('foo', 'bar');

		$container2 = new Everest\Container\Container;
		$container2->constant('hello2', 'world2');
		$container2->value('foo2', 'bar2');
		$container2->factory('fac', ['Options', function($options){
			return $options('a');
		}]);

		$app = (new Everest\App\App)
			->import($container)
			->import($container2)
			->factory('fac2', ['hello', function($world) use (&$called) {
				$this->assertSame('world', $world);
				return 2;
			}])
			->options(['a' => 1]);


		// Test Constant (without provider)
		$app->config(['hello', 'hello2', function($hello, $hello2) use (&$called){
			$this->assertEquals('world', $hello);
			$this->assertEquals('world2', $hello2);
			$called = true;
		}]);

		// Test value (with provider)
		$app->boot();

		$this->assertTrue($called);
		$this->assertSame(1, $app['fac']);
		$this->assertSame(2, $app['fac2']);
	}
}