<?php


use Everest\App\Provider\OptionsProvider;
use Everest\App\DelegateProviderInterface;
use Everest\App\Options;
use Everest\App\App;

use Everest\Container\FactoryProviderInterface;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class OptionsProviderTest extends \PHPUnit_Framework_TestCase {

	public function testProviderConstructionAndInterfaces()
	{
		$this->assertInstanceOf(OptionsProvider::CLASS, $provider = new OptionsProvider);
		$this->assertInstanceOf(DelegateProviderInterface::CLASS, $provider);
		$this->assertInstanceOf(FactoryProviderInterface::CLASS, $provider);
	}

	public function testFactory()
	{
		$provider = new OptionsProvider;
		$factory = $provider->getFactory();

		$this->assertTrue(is_array($factory));
		$this->assertTrue(is_callable($factory[0]));
	}

	public function testInitialAndRuntimeOptions()
	{
		$provider = new OptionsProvider(new Options(['initial' => 1]));
		$provider->add(new Options(['runtime' => 2]));

		$factory = $provider->getFactory();
		$options = $factory[0]();
		
		$this->assertSame(1, $options('initial'));
		$this->assertSame(2, $options('runtime'));
	}

	public function testProviderState()
	{
		$this->expectException(\RuntimeException::CLASS);

		$provider = new OptionsProvider;
		$provider->getFactory();
		$provider->add(new Options([]));
	}

	public function testInApp()
	{
		new App();
	}
}
