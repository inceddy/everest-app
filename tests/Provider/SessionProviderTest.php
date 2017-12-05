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
class SessionProviderTest extends \PHPUnit_Framework_TestCase {

	public function testProviderConstructionAndInterfaces()
	{
		$this->assertInstanceOf(SessionProvider::CLASS, $provider = new SessionProvider);
		$this->assertInstanceOf(FactoryProviderInterface::CLASS, $provider);
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
		$app->options('session', [
			'auto_start' => true,
			'options' => [
	      'secure'     => false,
	      'httponly'   => false,
	      'cookieonly' => true,
	      'lifetime'   => 1800,
	      'domain'     => 'test.com',
	      'path'       => '/'
			]
		]);
		$app->factory('SomeFactory', ['Session', function(Session $session) use (&$called) {
			$session->set('a', 'foo');
			$called = true;
		}]);

		// Supress error response cause of no matching route
		ob_start();
		$app->run(new ServerRequest(ServerRequest::HTTP_POST, Uri::from('test.com')));
		$app->SomeFactory;
		ob_end_clean();

		$this->assertTrue($called);
	}
}
