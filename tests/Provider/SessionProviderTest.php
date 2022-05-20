<?php


use Everest\App\Provider\SessionProvider;
use Everest\App\Session;
use Everest\App\App;

use Everest\Http\Requests\ServerRequest;
use Everest\Http\Uri;

use Everest\Container\FactoryProviderInterface;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class SessionProviderTest extends \PHPUnit\Framework\TestCase {

	public function testProviderConstructionAndInterfaces()
	{
		$this->assertInstanceOf(SessionProvider::class, $provider = new SessionProvider);
		$this->assertInstanceOf(FactoryProviderInterface::class, $provider);
	}

	public function testFactory()
	{
		$provider = new SessionProvider;
		$factory = $provider->getFactory();

		$this->assertTrue(is_array($factory));
		$this->assertTrue(is_callable(end($factory)));
	}

	public function testInApp()
	{
		$called = false;
		$app = new App();
		$app->options(['session' => [
			'auto_start' => true,
			'options' => [
	      'secure'     => false,
	      'httponly'   => false,
	      'cookieonly' => true,
	      'lifetime'   => 1800,
	      'domain'     => 'test.com',
	      'path'       => '/'
			]
		]]);
		$app->factory('SomeFactory', ['Session', function(Session $session) use (&$called) {
			$session->set('a', 'foo');
			$called = true;
		}]);
		$app->SomeFactory;

		$this->assertTrue($called);
	}
}
